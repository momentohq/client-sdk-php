<?php
declare(strict_types=1);

namespace Momento\Cache\Internal;

use Momento\Config\IConfiguration;
use Momento\Config\IStorageConfiguration;
use Momento\Storage\Internal\StorageDataClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class IdleStorageDataClientWrapper implements LoggerAwareInterface {

    private StorageDataClient $client;
    private LoggerInterface $logger;
    private object $clientFactory;
    private ?int $maxIdleMillis;
    private int $lastAccessTime;

    public function __construct(object $clientFactory, IStorageConfiguration $configuration) {
        $this->clientFactory = $clientFactory;
        $this->client = ($clientFactory->callback)();
        $this->logger = $configuration->getLoggerFactory()->getLogger(get_class($this));
        $this->maxIdleMillis = $configuration->getTransportStrategy()->getMaxIdleMillis();
        $this->lastAccessTime = $this->getMilliseconds();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getClient(): StorageDataClient {
        if ($this->maxIdleMillis === null) {
            return $this->client;
        }
        $this->logger->debug("Checking to see if client has been idle for more than {$this->maxIdleMillis}");
        if ($this->getMilliseconds() - $this->lastAccessTime > $this->maxIdleMillis) {
            $this->logger->debug("Client has been idle for more than {$this->maxIdleMillis}; reconnecting");
            $this->client->close();
            $this->client = ($this->clientFactory->callback)();
        }
        $this->lastAccessTime = $this->getMilliseconds();
        return $this->client;
    }

    public function close(): void {
        $this->client->close();
    }

    private function getMilliseconds(): int {
        return (int)(gettimeofday(true) * 1000);
    }
}
