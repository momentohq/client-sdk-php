<head>
  <meta name="Momento PHP Client Library Documentation" content="PHP client software development kit for Momento Serverless Cache">
</head>
<img src="https://docs.momentohq.com/img/logo.svg" alt="logo" width="400"/>

[![project status](https://momentohq.github.io/standards-and-practices/badges/project-status-official.svg)](https://github.com/momentohq/standards-and-practices/blob/main/docs/momento-on-github.md)
[![project stability](https://momentohq.github.io/standards-and-practices/badges/project-stability-alpha.svg)](https://github.com/momentohq/standards-and-practices/blob/main/docs/momento-on-github.md)

# Momento PHP Client Library

:warning: Alpha SDK :warning:

こちらの SDK は Momento の公式 SDK ですが、API は Alpha ステージです。
そのため、後方互換不可能な変更の対象になる可能性があります。詳細は上記の Alpha ボタンをクリックしてください。

Momento Serverless Cache の PHP クライアント SDK：従来のキャッシュが必要とするオペレーションオーバーヘッドが全く無く、速くて、シンプルで、従量課金のキャッシュです！

## さあ、使用開始 :running:

### 必要条件

- Momento Auth Token が必要です。[Momento CLI](https://github.com/momentohq/momento-cli)を使って生成できます。
- 少なくとも PHP 7
- grpc PHP エクステンション。 インストール方法はこちらの[gRPC docs](https://github.com/grpc/grpc/blob/v1.46.3/src/php/README.md)を参考にしてください。

**IDE に関する注意事項**: [PhpStorm](https://www.jetbrains.com/phpstorm/)や[Microsoft Visual Studio Code](https://code.visualstudio.com/)の様な PHP 開発をサポートできる IDE が必要となります。

### インストール

こちらのコンポーザーの[ウェブサイト](https://getcomposer.org/doc/00-intro.md)に掲載されているコンポーザーをインストールしてください。

私達のリポジトリを`composer.json`ファイルに追加し、SDK を依存パッケージとして追加してください：

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/momentohq/client-sdk-php"
    }
  ],
  "require": {
    "momentohq/client-sdk-php": "dev-main"
  }
}
```

`composer update`を実行し他の必要条件をインストールしてください。

### 使用方法

コード例は[examples ディレクトリ](examples/)を参照してください！

以下が簡単に Momento を使用開始する例です：

```php
<?php

require "vendor/autoload.php";

use Momento\Cache\SimpleCacheClient;

$MOMENTO_AUTH_TOKEN = getenv("MOMENTO_AUTH_TOKEN");
$CACHE_NAME = "cache";
$ITEM_DEFAULT_TTL_SECONDS = 60;
$KEY = "MyKey";
$VALUE = "MyValue";

function printBanner(string $message) : void {
    $line = "******************************************************************";
    print "$line\n$message\n$line\n";
}

function createCache(SimpleCacheClient $client, string $cacheName) : void {
    try {
        $client->createCache($cacheName);
    } catch (\Momento\Cache\Errors\AlreadyExistsError $e) {}
}

function listCaches(SimpleCacheClient $client) : void {
    $result = $client->listCaches();
    while (true) {
        foreach ($result->caches() as $cache) {
            print "- {$cache->name()}\n";
        }
        $nextToken = $result->nextToken();
        if (!$nextToken) {
            break;
        }
        $result = $client->listCaches($nextToken);
    }
}

printBanner("*                      Momento Example Start                     *");
$client = new SimpleCacheClient($MOMENTO_AUTH_TOKEN, $ITEM_DEFAULT_TTL_SECONDS);
createCache($client, $CACHE_NAME);
listCaches($client);
print "Setting key $KEY to value $VALUE\n";
$client->set($CACHE_NAME, $KEY, $VALUE);
$response = $client->get($CACHE_NAME, $KEY);
print "Look up status is: {$response->status()}\n";
print "Look up value is: {$response->value()}\n";
printBanner("*                       Momento Example End                      *");

```

### エラーの対処法

準備中です！

### チューニング

準備中です！

---

更なる詳細は私達のウェブサイト[https://jp.gomomento.com/](https://jp.gomomento.com/)をご確認ください！
