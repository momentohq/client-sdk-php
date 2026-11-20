<?php
declare(strict_types=1);

namespace Momento\Auth\Internal;

use Grpc\UnaryCall;
use Momento\Auth\AccessControl\CacheItemSelector;
use Momento\Auth\AccessControl\CachePermission;
use Momento\Auth\AccessControl\CacheRole;
use Momento\Auth\AccessControl\CacheSelector;
use Momento\Auth\AccessControl\DisposableTokenScope;
use Momento\Auth\AccessControl\TopicPermission;
use Momento\Auth\AccessControl\TopicRole;
use Momento\Auth\AuthOperationTypes\GenerateDisposableTokenError;
use Momento\Auth\AuthOperationTypes\GenerateDisposableTokenResponse;
use Momento\Auth\AuthOperationTypes\GenerateDisposableTokenSuccess;
use Momento\Auth\ITokenCredentialProvider;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IConfiguration;
use Momento\Utilities\_ErrorConverter;
use Permission_messages\CacheRole as GrpcCacheRole;
use Permission_messages\ExplicitPermissions;
use Permission_messages\Permissions;
use Permission_messages\PermissionsType;
use Permission_messages\PermissionsType\All;
use Permission_messages\PermissionsType\CacheItemSelector as GrpcCacheItemSelector;
use Permission_messages\PermissionsType\CachePermissions;
use Permission_messages\PermissionsType\CacheSelector as GrpcCacheSelector;
use Permission_messages\PermissionsType\TopicPermissions;
use Permission_messages\PermissionsType\TopicSelector as GrpcTopicSelector;
use Permission_messages\TopicRole as GrpcTopicRole;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Token\_GenerateDisposableTokenRequest;
use Token\_GenerateDisposableTokenRequest\Expires;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateNullOrEmpty;
use function Momento\Utilities\validateOperationTimeout;

class ScsTokenClient implements LoggerAwareInterface
{
    private const DEFAULT_DEADLINE_MILLISECONDS = 5000;
    private const TIMEOUT_MULTIPLIER = 1000;
    private const MAX_TOKEN_ID_LENGTH = 64;

    private const CACHE_ROLES = [
        CacheRole::READ_WRITE => GrpcCacheRole::CacheReadWrite,
        CacheRole::READ_ONLY => GrpcCacheRole::CacheReadOnly,
        CacheRole::WRITE_ONLY => GrpcCacheRole::CacheWriteOnly,
    ];

    private const TOPIC_ROLES = [
        TopicRole::PUBLISH_SUBSCRIBE => GrpcTopicRole::TopicReadWrite,
        TopicRole::SUBSCRIBE_ONLY => GrpcTopicRole::TopicReadOnly,
        TopicRole::PUBLISH_ONLY => GrpcTopicRole::TopicWriteOnly,
    ];

    private TokenGrpcManager $grpcManager;
    private string $authToken;
    private int $timeout;
    private LoggerInterface $logger;

    public function __construct(IConfiguration $configuration, ITokenCredentialProvider $authProvider)
    {
        $operationTimeoutMs = $configuration
            ->getTransportStrategy()
            ->getGrpcConfig()
            ->getDeadlineMilliseconds();
        validateOperationTimeout($operationTimeoutMs);
        $this->timeout = ($operationTimeoutMs ?? self::DEFAULT_DEADLINE_MILLISECONDS) * self::TIMEOUT_MULTIPLIER;
        $this->authToken = $authProvider->getAuthToken();
        $this->grpcManager = new TokenGrpcManager($authProvider);
        $this->setLogger($configuration->getLoggerFactory()->getLogger(get_class($this)));
    }

    public function close(): void
    {
        $this->grpcManager->close();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function processCall(UnaryCall $call)
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            throw _ErrorConverter::convert($status, $call->getMetadata());
        }
        return $response;
    }

    public function generateDisposableToken(DisposableTokenScope $scope, int $validForSeconds, ?string $tokenId = null): GenerateDisposableTokenResponse
    {
        try {
            if ($validForSeconds <= 0) {
                throw new InvalidArgumentError("Disposable token expiry must be greater than zero.");
            }
            $expires = new Expires();
            $expires->setValidForSeconds($validForSeconds);
            $request = new _GenerateDisposableTokenRequest();
            $request->setExpires($expires);
            $request->setAuthToken($this->authToken);
            $request->setPermissions($this->permissionsFromScope($scope));
            if ($tokenId !== null) {
                if (strlen($tokenId) > self::MAX_TOKEN_ID_LENGTH) {
                    throw new InvalidArgumentError("Token ID must be at most " . self::MAX_TOKEN_ID_LENGTH . " characters.");
                }
                $request->setTokenId($tokenId);
            }
            $call = $this->grpcManager->client->GenerateDisposableToken($request, [], ["timeout" => $this->timeout]);
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            $this->logger->debug("Failed to generate disposable token: {$e->getMessage()}");
            return new GenerateDisposableTokenError($e);
        } catch (\Exception $e) {
            $this->logger->debug("Failed to generate disposable token: {$e->getMessage()}");
            return new GenerateDisposableTokenError(new UnknownError($e->getMessage()));
        }
        return new GenerateDisposableTokenSuccess($response);
    }

    private function permissionsFromScope(DisposableTokenScope $scope): Permissions
    {
        $grpcPermissions = [];
        foreach ($scope->permissions() as $permission) {
            $grpcPermission = new PermissionsType();
            if ($permission instanceof CachePermission) {
                $grpcPermission->setCachePermissions($this->cachePermissionToGrpc($permission));
            } elseif ($permission instanceof TopicPermission) {
                $grpcPermission->setTopicPermissions($this->topicPermissionToGrpc($permission));
            } else {
                throw new InvalidArgumentError("Unrecognized disposable token permission.");
            }
            $grpcPermissions[] = $grpcPermission;
        }
        $explicit = new ExplicitPermissions();
        $explicit->setPermissions($grpcPermissions);
        $permissions = new Permissions();
        $permissions->setExplicit($explicit);
        return $permissions;
    }

    private function cachePermissionToGrpc(CachePermission $permission): CachePermissions
    {
        if (!array_key_exists($permission->role(), self::CACHE_ROLES)) {
            throw new InvalidArgumentError("Unrecognized cache role: {$permission->role()}");
        }
        $grpcPermission = new CachePermissions();
        $grpcPermission->setRole(self::CACHE_ROLES[$permission->role()]);
        if ($permission->cache()->isAllCaches()) {
            $grpcPermission->setAllCaches(new All());
        } else {
            $grpcPermission->setCacheSelector($this->cacheSelectorToGrpc($permission->cache()));
        }
        $item = $permission->item();
        if ($item !== null) {
            if ($item->isAllCacheItems()) {
                $grpcPermission->setAllItems(new All());
            } else {
                $grpcPermission->setItemSelector($this->cacheItemSelectorToGrpc($item));
            }
        }
        return $grpcPermission;
    }

    private function topicPermissionToGrpc(TopicPermission $permission): TopicPermissions
    {
        if (!array_key_exists($permission->role(), self::TOPIC_ROLES)) {
            throw new InvalidArgumentError("Unrecognized topic role: {$permission->role()}");
        }
        $grpcPermission = new TopicPermissions();
        $grpcPermission->setRole(self::TOPIC_ROLES[$permission->role()]);
        if ($permission->cache()->isAllCaches()) {
            $grpcPermission->setAllCaches(new All());
        } else {
            $grpcPermission->setCacheSelector($this->cacheSelectorToGrpc($permission->cache()));
        }
        if ($permission->topic()->isAllTopics()) {
            $grpcPermission->setAllTopics(new All());
        } else {
            validateNullOrEmpty($permission->topic()->name(), "Topic name");
            $grpcSelector = new GrpcTopicSelector();
            $grpcSelector->setTopicName($permission->topic()->name());
            $grpcPermission->setTopicSelector($grpcSelector);
        }
        return $grpcPermission;
    }

    private function cacheSelectorToGrpc(CacheSelector $selector): GrpcCacheSelector
    {
        validateCacheName($selector->name());
        $grpcSelector = new GrpcCacheSelector();
        $grpcSelector->setCacheName($selector->name());
        return $grpcSelector;
    }

    private function cacheItemSelectorToGrpc(CacheItemSelector $selector): GrpcCacheItemSelector
    {
        $grpcSelector = new GrpcCacheItemSelector();
        if ($selector->key() !== null) {
            validateNullOrEmpty($selector->key(), "Cache item key");
            $grpcSelector->setKey($selector->key());
        } else {
            validateNullOrEmpty($selector->keyPrefix(), "Cache item key prefix");
            $grpcSelector->setKeyPrefix($selector->keyPrefix());
        }
        return $grpcSelector;
    }
}
