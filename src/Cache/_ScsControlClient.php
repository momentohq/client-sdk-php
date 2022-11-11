<?php
declare(strict_types=1);

namespace Momento\Cache;

use Control_client\_CreateCacheRequest;
use Control_client\_DeleteCacheRequest;
use Control_client\_ListCachesRequest;
use Grpc\UnaryCall;
use Momento\Cache\CacheOperationTypes\CreateCacheResponse;
use Momento\Cache\CacheOperationTypes\CreateCacheResponseAlreadyExists;
use Momento\Cache\CacheOperationTypes\CreateCacheResponseError;
use Momento\Cache\CacheOperationTypes\CreateCacheResponseSuccess;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponse;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponseError;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponseSuccess;
use Momento\Cache\CacheOperationTypes\ListCacheResponseError;
use Momento\Cache\CacheOperationTypes\ListCachesResponse;
use Momento\Cache\CacheOperationTypes\ListCachesResponseError;
use Momento\Cache\CacheOperationTypes\ListCachesResponseSuccess;
use Momento\Cache\Errors\AlreadyExistsError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\validateCacheName;

class _ScsControlClient implements LoggerAwareInterface
{

    private _ControlGrpcManager $grpcManager;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $authToken, string $endpoint)
    {
        $this->grpcManager = new _ControlGrpcManager($authToken, $endpoint);
        $this->setLogger($logger);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function processCall(UnaryCall $call): mixed
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
        } catch (AlreadyExistsError) {
            return new CreateCacheResponseAlreadyExists();
        } catch (SdkError $e) {
            return new CreateCacheResponseError($e);
        } catch (\Exception $e) {
            return new CreateCacheResponseError(new UnknownError($e->getMessage()));
        }
        return new CreateCacheResponseSuccess();
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
            return new DeleteCacheResponseError($e);
        } catch (\Exception $e) {
            return new DeleteCacheResponseError(new UnknownError($e->getMessage()));
        }
        return new DeleteCacheResponseSuccess();
    }

    public function listCaches(?string $nextToken = null): ListCachesResponse
    {
        try {
            $request = new _ListCachesRequest();
            $request->setNextToken($nextToken ? $nextToken : "");
            $call = $this->grpcManager->client->ListCaches($request);
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new ListCachesResponseError($e);
        } catch (\Exception $e) {
            return new ListCachesResponseError(new UnknownError($e->getMessage()));
        }
        return new ListCachesResponseSuccess($response);
    }

}
