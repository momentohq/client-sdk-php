<?php
declare(strict_types=1);

namespace Momento\Cache\Internal;

use Control_client\_CreateCacheRequest;
use Control_client\_DeleteCacheRequest;
use Control_client\_ListCachesRequest;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\CreateCacheResponse;
use Momento\Cache\CacheOperationTypes\CreateCacheAlreadyExists;
use Momento\Cache\CacheOperationTypes\CreateCacheError;
use Momento\Cache\CacheOperationTypes\CreateCacheSuccess;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponse;
use Momento\Cache\CacheOperationTypes\DeleteCacheError;
use Momento\Cache\CacheOperationTypes\DeleteCacheSuccess;
use Momento\Cache\CacheOperationTypes\ListCachesResponse;
use Momento\Cache\CacheOperationTypes\ListCachesError;
use Momento\Cache\CacheOperationTypes\ListCachesSuccess;
use Momento\Cache\Errors\AlreadyExistsError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Logging\ILoggerFactory;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\validateCacheName;

class ScsControlClient implements LoggerAwareInterface
{

    private ControlGrpcManager $grpcManager;
    private ILoggerFactory $loggerFactory;
    private LoggerInterface $logger;

    public function __construct(ILoggerFactory $loggerFactory, ICredentialProvider $authProvider)
    {
        $this->grpcManager = new ControlGrpcManager($authProvider);
        $this->loggerFactory = $loggerFactory;
        $this->setLogger($this->loggerFactory->getLogger(get_class($this)));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function processCall(UnaryCall $call)
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            throw _ErrorConverter::convert($status->code, $status->details, $call->getMetadata());
        }
        return $response;
    }

    public function createCache(string $cacheName): CreateCacheResponse
    {
        try {
            validateCacheName($cacheName);
            $request = new _CreateCacheRequest();
            $request->setCacheName($cacheName);
            $call = $this->grpcManager->client->CreateCache($request);
            $this->processCall($call);
        } catch (AlreadyExistsError $e) {
            return new CreateCacheAlreadyExists();
        } catch (SdkError $e) {
            $this->logger->debug("Failed to create cache $cacheName: {$e->getMessage()}");
            return new CreateCacheError($e);
        } catch (\Exception $e) {
            $this->logger->debug("Failed to create cache $cacheName: {$e->getMessage()}");
            return new CreateCacheError(new UnknownError($e->getMessage()));
        }
        return new CreateCacheSuccess();
    }

    public function deleteCache(string $cacheName): DeleteCacheResponse
    {
        try {
            validateCacheName($cacheName);
            $request = new _DeleteCacheRequest();
            $request->setCacheName($cacheName);
            $call = $this->grpcManager->client->DeleteCache($request);
            $this->processCall($call);
        } catch (SdkError $e) {
            $this->logger->debug("Failed to delete cache $cacheName: {$e->getMessage()}");
            return new DeleteCacheError($e);
        } catch (\Exception $e) {
            $this->logger->debug("Failed to delete cache $cacheName: {$e->getMessage()}");
            return new DeleteCacheError(new UnknownError($e->getMessage()));
        }
        return new DeleteCacheSuccess();
    }

    public function listCaches(?string $nextToken = null): ListCachesResponse
    {
        try {
            $request = new _ListCachesRequest();
            $request->setNextToken($nextToken ? $nextToken : "");
            $call = $this->grpcManager->client->ListCaches($request);
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new ListCachesError($e);
        } catch (\Exception $e) {
            return new ListCachesError(new UnknownError($e->getMessage()));
        }
        return new ListCachesSuccess($response);
    }

}
