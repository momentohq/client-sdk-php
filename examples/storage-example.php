<?php
declare(strict_types=1);
require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Config\Configurations\Storage\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Psr\Log\LoggerInterface;
use Momento\Storage\PreviewStorageClient;
use Momento\Storage\StorageOperationTypes\StorageValueType;

$STORE_NAME = uniqid("php-storage-example-");
$VALUES = [
    "float" => 123.456,
    "str"=> "StringValue",
    "int" => 123,
    "fakeout" => "123"
];

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariable("MOMENTO_AUTH_TOKEN");
$configuration = Laptop::latest(new StderrLoggerFactory());
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
    $logger->info("Setting key: '$key' in '$STORE_NAME' to value: '$value', type = " . gettype($value) . "\n");
    if (is_int($value)) {
        $response = $client->putInt($STORE_NAME, $key, $value);
    } elseif (is_float($value)) {
        $response = $client->putFloat($STORE_NAME, $key, $value);
    } elseif (is_string($value)) {
        $response = $client->putString($STORE_NAME, $key, $value);
    } else {
        $logger->info("Unsupported value type: " . get_class($value) . "\n");
        deleteStore($STORE_NAME, $logger, $client);
        exit(1);
    }
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
    if ($found = $response->asFound()) {
        $logger->info("SUCCESS\n");
        $valueType = $found->type();
        if ($valueType == StorageValueType::STRING) {
            print("Got string value: " . $found->valueString() . "\n");
        } elseif ($valueType == StorageValueType::INT) {
            print("Got integer value: " . $found->valueInt() . "\n");
        } elseif ($valueType == StorageValueType::FLOAT) {
            print("Got double value: " . $found->valueFloat() . "\n");
        } elseif ($valueType == StorageValueType::BYTES) {
            // This case is not expected in this example as PHP doesn't have a native byte type
            print("Got bytes value: " . $found->valueBytes() . "\n");
        }
        // Raw value is also available, and is being pulled directly from the response without
        // casting to Found. All other `valueXYZ()` methods can also be called directly on the response.
        print("Raw value: " . $response->value() . "\n");
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
