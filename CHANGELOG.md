# Changelog

## [1.11.0](https://github.com/momentohq/client-sdk-php/compare/v1.0.0...v1.11.0) (2024-07-08)


### Features

* add `close` method for explicitly closing client ([#174](https://github.com/momentohq/client-sdk-php/issues/174)) ([0cb716e](https://github.com/momentohq/client-sdk-php/commit/0cb716e5f8a9c7d0f6bb29ac332363bbee8fc6fc))
* add config setting to force new channel ([#159](https://github.com/momentohq/client-sdk-php/issues/159)) ([20150d3](https://github.com/momentohq/client-sdk-php/commit/20150d3ac39c1e289ae6fe40528d8244772ec7a1))
* add configurability for number of gRPC channels ([#158](https://github.com/momentohq/client-sdk-php/issues/158)) ([81e256d](https://github.com/momentohq/client-sdk-php/commit/81e256d070bbb486735562843a72308e622aeee6))
* add flush cache operation ([#173](https://github.com/momentohq/client-sdk-php/issues/173)) ([fe488ac](https://github.com/momentohq/client-sdk-php/commit/fe488ac197ac8935e9f487e9d6bf7e7568792523))
* add getBatch and setBatch ([#177](https://github.com/momentohq/client-sdk-php/issues/177)) ([9f0a0b1](https://github.com/momentohq/client-sdk-php/commit/9f0a0b109eeb1ce5263fd2ab8b36c01617bc9e71))
* add idle data client wrapper ([#137](https://github.com/momentohq/client-sdk-php/issues/137)) ([f58b9df](https://github.com/momentohq/client-sdk-php/commit/f58b9dfa443471033efdd734c4da430a8091386f))
* add increment operation ([#130](https://github.com/momentohq/client-sdk-php/issues/130)) ([7004fa1](https://github.com/momentohq/client-sdk-php/commit/7004fa1ce4d1dc19a43039740b6aa7d47c84ccf3))
* add SetAddElements API ([#152](https://github.com/momentohq/client-sdk-php/issues/152)) ([ec2cf8b](https://github.com/momentohq/client-sdk-php/commit/ec2cf8bd7d6aadaf3cccb676ea693f2cb97d624b))
* add SetIf operations ([#189](https://github.com/momentohq/client-sdk-php/issues/189)) ([016b5dd](https://github.com/momentohq/client-sdk-php/commit/016b5dd70ab6cf12fb87300bdc9743bdc9276d4f))
* add support for V1 auth tokens ([#138](https://github.com/momentohq/client-sdk-php/issues/138)) ([187c2e1](https://github.com/momentohq/client-sdk-php/commit/187c2e1e1a7c1f7d483b28f0bd2331df8d9a26f0))
* adding storage client ([#191](https://github.com/momentohq/client-sdk-php/issues/191)) ([d6bcc98](https://github.com/momentohq/client-sdk-php/commit/d6bcc98139af4aa21c30bcfe61517314d3e0a637))
* async client ([#157](https://github.com/momentohq/client-sdk-php/issues/157)) ([87795cd](https://github.com/momentohq/client-sdk-php/commit/87795cda24838694a2741429566c82239f452971))
* implement SetLength API ([#156](https://github.com/momentohq/client-sdk-php/issues/156)) ([0cc59d7](https://github.com/momentohq/client-sdk-php/commit/0cc59d75ec9e9f9928cc54a85620c4c1447b45d0))
* Improve PSR cache multiple operation performance ([#165](https://github.com/momentohq/client-sdk-php/issues/165)) ([7227c08](https://github.com/momentohq/client-sdk-php/commit/7227c08c2310a1a0cefda2157a23f537639a84c6))
* one time headers ([#192](https://github.com/momentohq/client-sdk-php/issues/192)) ([e48a8d4](https://github.com/momentohq/client-sdk-php/commit/e48a8d4adc5ae7fb70a80ade2a53646372a785bd))
* PHP 7.4 support ([#171](https://github.com/momentohq/client-sdk-php/issues/171)) ([f06e97d](https://github.com/momentohq/client-sdk-php/commit/f06e97de4aa8f2eacc6135f6cb2c343fba41b334))
* Support fractional TTLs via floats/doubles ([#187](https://github.com/momentohq/client-sdk-php/issues/187)) ([7adb5e6](https://github.com/momentohq/client-sdk-php/commit/7adb5e6b1bb9a82622bd8ba78a3dcff991f294c0))


### Bug Fixes

* allow users to override PSR-16 cache name ([#166](https://github.com/momentohq/client-sdk-php/issues/166)) ([ca6412b](https://github.com/momentohq/client-sdk-php/commit/ca6412b02ea18b835443c5cacec4b0d479431c70))
* bump version for examples ([#124](https://github.com/momentohq/client-sdk-php/issues/124)) ([b93973c](https://github.com/momentohq/client-sdk-php/commit/b93973ce656f742bde2b7128682e48339c2e6dcf))
* bump version in README ([#126](https://github.com/momentohq/client-sdk-php/issues/126)) ([44a3035](https://github.com/momentohq/client-sdk-php/commit/44a30354eb74a9342f6c6d032e7f63f717ba34b0))
* Correct broken InRegion configuration ([#163](https://github.com/momentohq/client-sdk-php/issues/163)) ([1535cf6](https://github.com/momentohq/client-sdk-php/commit/1535cf6713c5b03ca8c16e99686d5aa97ea4f00e))
* Corrected versions in the README ([#131](https://github.com/momentohq/client-sdk-php/issues/131)) ([d6cbbef](https://github.com/momentohq/client-sdk-php/commit/d6cbbefd031415e849657b842270b5689235925e))
* deprecate setIfNotExists and use setIfAbsent in data client ([#190](https://github.com/momentohq/client-sdk-php/issues/190)) ([65abdd8](https://github.com/momentohq/client-sdk-php/commit/65abdd8623cfeb749a57e84bd15dad84bf6cc108))
* explicitly close gRPC channel when cycling idle client ([#140](https://github.com/momentohq/client-sdk-php/issues/140)) ([82d120d](https://github.com/momentohq/client-sdk-php/commit/82d120d639362e83f043ddc9dd2901e646cf93ce))
* fix readme generation action version ([#145](https://github.com/momentohq/client-sdk-php/issues/145)) ([9d5ea5b](https://github.com/momentohq/client-sdk-php/commit/9d5ea5bb85581447d65eb2ad3e4a34361e5a9be8))
* fix stderr logger log method signature ([#147](https://github.com/momentohq/client-sdk-php/issues/147)) ([69b2a1e](https://github.com/momentohq/client-sdk-php/commit/69b2a1e5a540781631e1c9caddb784efb7ea7007))
* fix stray project stability attr ([#123](https://github.com/momentohq/client-sdk-php/issues/123)) ([9103206](https://github.com/momentohq/client-sdk-php/commit/910320663299b902c938764456a21e63c171dedc))
* move max idle millis to a static var in Configuation ([#141](https://github.com/momentohq/client-sdk-php/issues/141)) ([b380fc0](https://github.com/momentohq/client-sdk-php/commit/b380fc07be53b3213407e6de0c31497938d857d6))
* update dockerfile ([#181](https://github.com/momentohq/client-sdk-php/issues/181)) ([4fb8805](https://github.com/momentohq/client-sdk-php/commit/4fb88057f3a4cdad2f186c811043c171e1d10ce4))
* use $_SERVER instead of getenv ([#153](https://github.com/momentohq/client-sdk-php/issues/153)) ([4e41cea](https://github.com/momentohq/client-sdk-php/commit/4e41cea44be21c18f6352796c172f0040443c552))


### Miscellaneous

* add docs for protobuf extension and other improvements ([#182](https://github.com/momentohq/client-sdk-php/issues/182)) ([164c67b](https://github.com/momentohq/client-sdk-php/commit/164c67b1a43c828153425d40b586db030e03c5b1))
* add php doc examples ([#144](https://github.com/momentohq/client-sdk-php/issues/144)) ([9b8ff4f](https://github.com/momentohq/client-sdk-php/commit/9b8ff4f0b49efed0c87d768e8d792eff5c2c7d73))
* add release please workflow ([#199](https://github.com/momentohq/client-sdk-php/issues/199)) ([d0c6137](https://github.com/momentohq/client-sdk-php/commit/d0c6137dd707719d366be4f2ef474fcb42b90379))
* add support for psr/log v3 ([#142](https://github.com/momentohq/client-sdk-php/issues/142)) ([5570fdf](https://github.com/momentohq/client-sdk-php/commit/5570fdf0e19db89685b521bd81028077b9c1bc1b))
* add version matrix for testing ([#172](https://github.com/momentohq/client-sdk-php/issues/172)) ([30106c6](https://github.com/momentohq/client-sdk-php/commit/30106c65fba241ddb89c67813ae6db6a9b0497f0))
* clean up docs and removed unused TEST_CACHE_NAME ([#188](https://github.com/momentohq/client-sdk-php/issues/188)) ([6387ff3](https://github.com/momentohq/client-sdk-php/commit/6387ff35062813062828ffbdfe7bef741a1858e8))
* **deps-dev:** bump composer/composer from 2.7.1 to 2.7.7 ([#196](https://github.com/momentohq/client-sdk-php/issues/196)) ([c7dfa7d](https://github.com/momentohq/client-sdk-php/commit/c7dfa7da3d03a84ca01f180c974403d40dca8a7d))
* release 1.11.0 ([#201](https://github.com/momentohq/client-sdk-php/issues/201)) ([55fa70a](https://github.com/momentohq/client-sdk-php/commit/55fa70a571ef6e3bb7cbab6745aa53fc0fe6ec86))
* require protobuf depedency ([#179](https://github.com/momentohq/client-sdk-php/issues/179)) ([1a0b4d5](https://github.com/momentohq/client-sdk-php/commit/1a0b4d5f5af109b0bdaea0f884e13bd6adc5b94b))
* update examples and READMES for v1.3.0 ([#167](https://github.com/momentohq/client-sdk-php/issues/167)) ([1938cfe](https://github.com/momentohq/client-sdk-php/commit/1938cfedbb56472ed649512478100e2dbe195450))
* update examples for SDK v1.2.0 ([#143](https://github.com/momentohq/client-sdk-php/issues/143)) ([999a08a](https://github.com/momentohq/client-sdk-php/commit/999a08a1a5eff59e109ab17f363b9df407a92516))
* update grpc version to 1.57.0 ([#180](https://github.com/momentohq/client-sdk-php/issues/180)) ([000c265](https://github.com/momentohq/client-sdk-php/commit/000c265ed962267ac483d78b8759cb51378c84ac))
* update JP ver. of PHP README ([#128](https://github.com/momentohq/client-sdk-php/issues/128)) ([f4163d0](https://github.com/momentohq/client-sdk-php/commit/f4163d0bb8d7f347040a6134f9e6b7852b66636d))
* update metadata message signifying item not found ([#193](https://github.com/momentohq/client-sdk-php/issues/193)) ([e5ea030](https://github.com/momentohq/client-sdk-php/commit/e5ea0304c26d4c641a137c395e6201186691a816))
* update proto generate types to pick up latest APIs ([#155](https://github.com/momentohq/client-sdk-php/issues/155)) ([743ed13](https://github.com/momentohq/client-sdk-php/commit/743ed13928a91e8e7cb7d3618ed8bb6909229f52))
* update protos to include new SetIf types ([#186](https://github.com/momentohq/client-sdk-php/issues/186)) ([2eb6160](https://github.com/momentohq/client-sdk-php/commit/2eb616092a44c2b5faed8b546b31c496ff600212))
* Update README.md to include auth token generation instruction ([#169](https://github.com/momentohq/client-sdk-php/issues/169)) ([fd39e4c](https://github.com/momentohq/client-sdk-php/commit/fd39e4cf4247bb0aa01d7676a4887422ca4ec0d7))
