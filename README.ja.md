<head>
  <meta name="Momento PHP Client Library Documentation" content="PHP client software development kit for Momento Serverless Cache">
</head>
<img src="https://docs.momentohq.com/img/logo.svg" alt="logo" width="400"/>

[![project status](https://momentohq.github.io/standards-and-practices/badges/project-status-official.svg)](https://github.com/momentohq/standards-and-practices/blob/main/docs/momento-on-github.md)
[![project stability](https://momentohq.github.io/standards-and-practices/badges/project-stability-stable.svg)](https://github.com/momentohq/standards-and-practices/blob/main/docs/momento-on-github.md)

# Momento PHP Client Library

Momento Serverless Cache の PHP クライアント SDK：従来のキャッシュが必要とするオペレーションオーバーヘッドが全く無く、速くて、シンプルで、従量課金のキャッシュです！

## さあ、使用開始 :running:

### 必要条件

-   Momento Auth Token が必要です。[Momento CLI](https://github.com/momentohq/momento-cli)を使って生成できます。
-   少なくとも PHP 8.0
-   grpc PHP エクステンション。 インストール方法はこちらの[gRPC docs](https://github.com/grpc/grpc/blob/v1.54.0/src/php/README.md)を参考にしてください。

**IDE に関する注意事項**: [PhpStorm](https://www.jetbrains.com/phpstorm/)
や[Microsoft Visual Studio Code](https://code.visualstudio.com/)の様な PHP 開発をサポートできる IDE が必要となります。

### 例

[こちらの例ディレクトリ](https://github.com/momentohq/client-sdk-php/tree/main/examples)をご参照ください！
大半の例に使用されているメインの Momento `CacheClient`に加えて、PHP PSR-16 の実装例も SDK に含まれています。PSR-16 に関してはこちらの[README](https://github.com/momentohq/client-sdk-php/blob/main/README-PSR16.md)と[例](https://github.com/momentohq/client-sdk-php/blob/psr16-library/examples/psr16-example.php)をご参照ください。

### インストール

こちらのコンポーザーの[ウェブサイト](https://getcomposer.org/doc/00-intro.md)に掲載されているコンポーザーをインストールしてください。

私達のリポジトリを`composer.json`ファイルに追加し、SDK を依存パッケージとして追加してください：

```json
{
    "require": {
        "momentohq/client-sdk-php": "^1.3"
    }
}
```

`composer update`を実行し他の必要条件をインストールしてください。

### 使用方法

コード例は[examples ディレクトリ](examples/)を参照してください！

以下が簡単に Momento を使用開始する例です：

```php
<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Psr\Log\LoggerInterface;

$CACHE_NAME = uniqid("php-example-");
$ITEM_DEFAULT_TTL_SECONDS = 60;
$KEY = "MyKey";
$VALUE = "MyValue";

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY");
$configuration = Laptop::latest(new StderrLoggerFactory());
$client = new CacheClient($configuration, $authProvider, $ITEM_DEFAULT_TTL_SECONDS);
$logger = $configuration->getLoggerFactory()->getLogger("ex:");

function printBanner(string $message, LoggerInterface $logger): void
{
    $line = "******************************************************************";
    $logger->info($line);
    $logger->info($message);
    $logger->info($line);
}

printBanner("*                      Momento Example Start                     *", $logger);

// Ensure test cache exists
$response = $client->createCache($CACHE_NAME);
if ($response->asSuccess()) {
    $logger->info("Created cache " . $CACHE_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error creating cache: " . $response->asError()->message() . "\n");
    exit;
} elseif ($response->asAlreadyExists()) {
    $logger->info("Cache " . $CACHE_NAME . " already exists.\n");
}

// List cache
$response = $client->listCaches();
if ($response->asSuccess()) {
    $logger->info("SUCCESS: List caches: \n");
    foreach ($response->asSuccess()->caches() as $cache) {
        $cacheName = $cache->name();
        $logger->info("$cacheName\n");
    }
    $logger->info("\n");
} elseif ($response->asError()) {
    $logger->info("Error listing cache: " . $response->asError()->message() . "\n");
    exit;
}

// Set
$logger->info("Setting key: $KEY to value: $VALUE\n");
$response = $client->set($CACHE_NAME, $KEY, $VALUE);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: - Set key: " . $KEY . " value: " . $VALUE . " cache: " . $CACHE_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error setting key: " . $response->asError()->message() . "\n");
    exit;
}

// Get
$logger->info("Getting value for key: $KEY\n");
$response = $client->get($CACHE_NAME, $KEY);
if ($response->asHit()) {
    $logger->info("SUCCESS: - Get key: " . $KEY . " value: " . $response->asHit()->valueString() . " cache: " . $CACHE_NAME . "\n");
} elseif ($response->asMiss()) {
    $logger->info("Get operation was a MISS\n");
} elseif ($response->asError()) {
    $logger->info("Error getting cache: " . $response->asError()->message() . "\n");
    exit;
}

// Delete test cache
$logger->info("Deleting cache $CACHE_NAME\n");
$response = $client->deleteCache($CACHE_NAME);
if ($response->asError()) {
    $logger->info("Error deleting cache: " . $response->asError()->message() . "\n");
}

printBanner("*                       Momento Example End                      *", $logger);
```

`ClientCache`でのログに関する詳細はこちらの(README-logging.md)[https://github.com/momentohq/client-sdk-php/blob/main/README-logging.md]をご覧ください。

### エラーの対処法

`CacheClient` 関数の呼び出しの際に起こるエラーは、エクセプションとしてではなく返却値の一部として現れます。こうする事で、可視性が高まり IDE がより一層ユーザーが必要だと思う値に対して役立ちます。
（こちらの哲学に関する詳細は、[なぜエクセプションはバグなのか](https://www.gomomento.com/blog/exceptions-are-bugs)、という私達のブログを参照してください。そしてこれに関するフィードバックもお待ちしております！）
私たちが推薦している`CacheClient` 関数の返却値の対処の方法は`as`　関数を使用して返却タイプを特定し処理する方法です。こちらが簡単な例になります：

```php
$getResponse = $client->get($CACHE_NAME, $KEY);
if ($hitResponse = $getResponse->asHit())
{
    print "Looked up value: {$hitResponse->value()}\n");
} else {
    // you can handle other cases via pattern matching in `else if` blocks, or a default case
    // via the `else` block.  For each return value your IDE should be able to give you code
    // completion indicating the other possible "as" methods; in this case, `$getResponse->asMiss()`
    // and `$getResponse->asError()`.
}
```

このアプローチを使用することにより、キャッシュヒットの場合タイプが保証された`hitResponse` オブジェクトを取得することができます。
しかし、キャッシュの読み込みの結果がミスやエラーの場合、各タイプが保証されたオブジェクトを取得し、何が起こったかの詳細を確認することができます。

エラーのレスポンスを取得した場合、エラーのタイプを確認することのできる`ErrorCode`　がオブジェクトに必ず含まれます：

```php
$getResponse = $client->get($CACHE_NAME, $KEY);
if ($errorResponse = $getResponse->asError())
{
    if ($errorResponse->errorCode() == MomentoErrorCode::TIMEOUT_ERROR) {
       // this would represent a client-side timeout, and you could fall back to your original data source
    }
}
```

`CacheClient`のレスポンス外でエクセプションが起こり得るので、こちらは随時対処する必要があります。例えば、`CacheClient`オブジェクトを無効な認証トークンを使用して生成しようとした場合、`IllegalArgumentException`が投げられます。

### チューニング

準備中です！

---

更なる詳細は私達のウェブサイト[https://jp.gomomento.com/](https://jp.gomomento.com/)をご確認ください！
