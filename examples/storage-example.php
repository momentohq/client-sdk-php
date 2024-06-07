<?php
declare(strict_types=1);
require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Config\Configurations\StorageLaptop;
use Momento\Logging\StderrLoggerFactory;
use Psr\Log\LoggerInterface;
use Momento\Storage\PreviewStorageClient;
use Momento\Storage\StorageOperationTypes\StorageValueType;

$STORE_NAME = uniqid("php-storage-example-");
$VALUES = [
    "str"=> "StringValue",
    "int" => 123,
    "double" => 123.456,
    "fakeout" => "123"
];

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariable("MOMENTO_AUTH_TOKEN");
$configuration = StorageLaptop::latest(new StderrLoggerFactory());
$client = new PreviewStorageClient($configuration, $authProvider);
$logger = $configuration->getLoggerFactory()->getLogger("ex:");

function printBanner(string $message, LoggerInterface $logger): void
{
    $line = "**************************************************************************";
    $logger->info($line);
    $logger->info($message);
    $logger->info($line);
}

// Used to tear down temporary store after failure or completion of script
function deleteStore(string $storeName, LoggerInterface $logger, PreviewStorageClient $client): void
{
    $logger->info("Deleting store $storeName\n");
    $response = $client->deleteStore($storeName);
    if ($response->asError()) {
        $logger->info("Error deleting store: " . $response->asError()->message() . "\n");
        exit(1);
    }
}

printBanner("*                      Momento Storage Example Start                     *", $logger);

// Ensure test store exists
$response = $client->createStore($STORE_NAME);
if ($response->asSuccess()) {
    $logger->info("Created store " . $STORE_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error creating store: " . $response->asError()->message() . "\n");
    exit(1);
} elseif ($response->asAlreadyExists()) {
    $logger->info("Store " . $STORE_NAME . " already exists.\n");
}

// List stores
$response = $client->listStores();
if ($response->asSuccess()) {
    $logger->info("SUCCESS: List stores: \n");
    foreach ($response->asSuccess()->stores() as $store) {
        $storeName = $store->name();
        $logger->info("$storeName\n");
    }
    $logger->info("\n");
} elseif ($response->asError()) {
    $logger->info("Error listing store: " . $response->asError()->message() . "\n");
    deleteStore($STORE_NAME, $logger, $client);
    exit(1);
}

// Set
foreach ($VALUES as $key => $value) {
    $logger->info("Setting key: '$key' to value: '$value', type = " . get_class($value) . "\n");
    $response = $client->set($STORE_NAME, $key, $value);
    if ($response->asSuccess()) {
        $logger->info("SUCCESS\n");
    } elseif ($response->asError()) {
        $logger->info("Error setting key: " . $response->asError()->message() . "\n");
        deleteStore($STORE_NAME, $logger, $client);
        exit(1);
    }
}

// Get
foreach ($VALUES as $key => $value) {
    $logger->info("Getting value for key: '$key'\n");
    $response = $client->get($STORE_NAME, $key);
    if ($response->asSuccess()) {
        $logger->info("SUCCESS\n");
        $valueType = $response->asSuccess()->type();
        if ($valueType == StorageValueType::STRING) {
            print("Got string value: " . $response->asSuccess()->tryGetString() . "\n");
        } elseif ($valueType == StorageValueType::INTEGER) {
            print("Got integer value: " . $response->asSuccess()->tryGetInt() . "\n");
        } elseif ($valueType == StorageValueType::DOUBLE) {
            print("Got double value: " . $response->asSuccess()->tryGetDouble() . "\n");
        }
    } elseif ($response->asError()) {
        $logger->info("Error getting key: " . $response->asError()->message() . "\n");
        deleteStore($STORE_NAME, $logger, $client);
        exit(1);
    }
}

// Delete
foreach (array_keys($VALUES) as $key) {
    $logger->info("Deleting key: '$key'\n");
    $response = $client->delete($STORE_NAME, $key);
    if ($response->asSuccess()) {
        $logger->info("SUCCESS\n");
    } elseif ($response->asError()) {
        $logger->info("Error deleting key: " . $response->asError()->message() . "\n");
        exit(1);
    }
}

// Delete store
deleteStore($STORE_NAME, $logger, $client);

printBanner("*                       Momento Storage Example End                      *", $logger);
