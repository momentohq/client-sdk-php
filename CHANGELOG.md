# Changelog

## [1.19.1](https://github.com/momentohq/client-sdk-php/compare/v1.19.0...v1.19.1) (2026-02-19)


### Miscellaneous

* upgrade examples dep and restore examples checks in CI/CD ([#269](https://github.com/momentohq/client-sdk-php/issues/269)) ([ba19931](https://github.com/momentohq/client-sdk-php/commit/ba19931548564dd3a58233d45a8f9c112dccd2e5))

## [1.19.0](https://github.com/momentohq/client-sdk-php/compare/v1.18.0...v1.19.0) (2026-02-19)


### Features

* Remove firebase dependency ([#267](https://github.com/momentohq/client-sdk-php/issues/267)) ([5dc133e](https://github.com/momentohq/client-sdk-php/commit/5dc133e08da4ca8b658e17a18827f3d43d6d04ff))


### Miscellaneous

* add separate default env var v2 docs snippet ([#265](https://github.com/momentohq/client-sdk-php/issues/265)) ([a4b285d](https://github.com/momentohq/client-sdk-php/commit/a4b285dea990db3cdc97e8baa1d88be788e8541b))
* update examples, tests, readmes, CI for api keys v2 ([#263](https://github.com/momentohq/client-sdk-php/issues/263)) ([5a12d1f](https://github.com/momentohq/client-sdk-php/commit/5a12d1fde77040fa41a1fb3a365230a542377f06))

## [1.18.0](https://github.com/momentohq/client-sdk-php/compare/v1.17.1...v1.18.0) (2025-12-15)


### Features

* new credential provider methods for accepting v2 api keys ([#261](https://github.com/momentohq/client-sdk-php/issues/261)) ([80a74c7](https://github.com/momentohq/client-sdk-php/commit/80a74c7a62617be977b7edea4f5bc1c8ea94e72b))

## [1.17.1](https://github.com/momentohq/client-sdk-php/compare/v1.17.0...v1.17.1) (2025-07-15)


### Bug Fixes

* correct small issues with the configurations. ([#258](https://github.com/momentohq/client-sdk-php/issues/258)) ([c26fca9](https://github.com/momentohq/client-sdk-php/commit/c26fca99b0523a325474892006a24c5a8c187e36))
* disable dynamic DNS service config ([#254](https://github.com/momentohq/client-sdk-php/issues/254)) ([ba47637](https://github.com/momentohq/client-sdk-php/commit/ba47637651bd9426e481d38558d1bbf1b06447e4))


### Miscellaneous

* add debug logs for data client operations ([#260](https://github.com/momentohq/client-sdk-php/issues/260)) ([6e15e97](https://github.com/momentohq/client-sdk-php/commit/6e15e9794e967fe9445853ee13d9ae0720d72116))
* adding more testing for sorted set union store ([#256](https://github.com/momentohq/client-sdk-php/issues/256)) ([4a516ae](https://github.com/momentohq/client-sdk-php/commit/4a516ae634630684362e9f2954adb6859630a9a1))
* remove the storage client ([#259](https://github.com/momentohq/client-sdk-php/issues/259)) ([3d8f577](https://github.com/momentohq/client-sdk-php/commit/3d8f577f0feb3bc927e9574a4302600f90b97fe2))

## [1.17.0](https://github.com/momentohq/client-sdk-php/compare/v1.16.0...v1.17.0) (2025-03-04)


### Features

* adding SortedSetUnionStore operation ([#253](https://github.com/momentohq/client-sdk-php/issues/253)) ([f0e1483](https://github.com/momentohq/client-sdk-php/commit/f0e1483f7449363cec5e93721912a12111991405))

## [1.16.0](https://github.com/momentohq/client-sdk-php/compare/v1.15.1...v1.16.0) (2024-11-22)


### Features

* add dev-container documentation ([#248](https://github.com/momentohq/client-sdk-php/issues/248)) ([2394b93](https://github.com/momentohq/client-sdk-php/commit/2394b93a7196a873ff6bd698bbc5ee3a661ccf39))


### Miscellaneous

* add Lambda configuration with values ported from nodejs ([#251](https://github.com/momentohq/client-sdk-php/issues/251)) ([6c9c373](https://github.com/momentohq/client-sdk-php/commit/6c9c373f7abc23e89a1a9ce8a1c4c66a8b374550))
* fix license copyright info ([#252](https://github.com/momentohq/client-sdk-php/issues/252)) ([74bd347](https://github.com/momentohq/client-sdk-php/commit/74bd347114bc7e1eed6f25d2059c511b4090926a))

## [1.15.1](https://github.com/momentohq/client-sdk-php/compare/v1.15.0...v1.15.1) (2024-11-05)


### Miscellaneous

* improve resource exhausted error message ([#247](https://github.com/momentohq/client-sdk-php/issues/247)) ([c29417e](https://github.com/momentohq/client-sdk-php/commit/c29417ee8f746147cfce145ece9112343c43f4ca))

## [1.15.0](https://github.com/momentohq/client-sdk-php/compare/v1.14.0...v1.15.0) (2024-11-05)


### Features

* add ReadConcern config ([#246](https://github.com/momentohq/client-sdk-php/issues/246)) ([d263e18](https://github.com/momentohq/client-sdk-php/commit/d263e182931823063f703e4b0345950ec5772275))
* add sorted-set examples ([#241](https://github.com/momentohq/client-sdk-php/issues/241)) ([1906221](https://github.com/momentohq/client-sdk-php/commit/19062216f82656d24c79c0f0386cf5009a7797b9))


### Miscellaneous

* add named argument sorted-set example ([#244](https://github.com/momentohq/client-sdk-php/issues/244)) ([96412ba](https://github.com/momentohq/client-sdk-php/commit/96412bad6426ebed0db210ec4e768c79b0f29692))

## [1.14.0](https://github.com/momentohq/client-sdk-php/compare/v1.13.0...v1.14.0) (2024-10-25)


### Features

* add flags for toggling inclusivity on fetchByScore scores ([#238](https://github.com/momentohq/client-sdk-php/issues/238)) ([a8cfed8](https://github.com/momentohq/client-sdk-php/commit/a8cfed84a6c56ae647edaa74d7a326efea202912))
* add flags for toggling inclusivity on length by score scores ([#237](https://github.com/momentohq/client-sdk-php/issues/237)) ([1b150b4](https://github.com/momentohq/client-sdk-php/commit/1b150b4aa9594cb0856b3a2f0d003438d7d16285))
* add sorted set increment score ([#229](https://github.com/momentohq/client-sdk-php/issues/229)) ([d704d4e](https://github.com/momentohq/client-sdk-php/commit/d704d4e43cd1109e5c2433011fefb07978e6963b))
* add sorted set put element and fetch by rank ([#218](https://github.com/momentohq/client-sdk-php/issues/218)) ([12d92fe](https://github.com/momentohq/client-sdk-php/commit/12d92fe3ee2671844a27b53578f564c7d938cdbc))
* add sorted set remove elements ([#227](https://github.com/momentohq/client-sdk-php/issues/227)) ([1f44343](https://github.com/momentohq/client-sdk-php/commit/1f443435bc3b90ba98bbc9cde910f00918f406d8))
* add sortedSetFetchByScore ([#230](https://github.com/momentohq/client-sdk-php/issues/230)) ([9e62367](https://github.com/momentohq/client-sdk-php/commit/9e6236716bc608dbec9f6f53b35d6b35c9169a5b))
* add sortedSetGetScore ([#221](https://github.com/momentohq/client-sdk-php/issues/221)) ([0cde7a4](https://github.com/momentohq/client-sdk-php/commit/0cde7a48c6e94c86ff268e3b9625b2bf7c408e04))
* add sortedSetLengthByScore ([#234](https://github.com/momentohq/client-sdk-php/issues/234)) ([7b4ff9e](https://github.com/momentohq/client-sdk-php/commit/7b4ff9eada9ed7202e5c5cad9c6fee18aea1ffa9))
* add sortedSetPutElements ([#226](https://github.com/momentohq/client-sdk-php/issues/226)) ([fa68601](https://github.com/momentohq/client-sdk-php/commit/fa6860132c7d4187565938b72babba9d42ac7703))
* add sortedSetRemoveElement api ([#222](https://github.com/momentohq/client-sdk-php/issues/222)) ([f1948f2](https://github.com/momentohq/client-sdk-php/commit/f1948f219d274a6a17594045b50b84746ebfe828))


### Bug Fixes

* doc strings ([#217](https://github.com/momentohq/client-sdk-php/issues/217)) ([759c9e4](https://github.com/momentohq/client-sdk-php/commit/759c9e4318a2fee1859e095a0c6d3cfb248fcdae))
* initializing typed property  in sortedSetFetchHit ([#232](https://github.com/momentohq/client-sdk-php/issues/232)) ([d5719a8](https://github.com/momentohq/client-sdk-php/commit/d5719a86367d08072ea63a51673328223582b3bf))
* stop using is_string() for validation ([#233](https://github.com/momentohq/client-sdk-php/issues/233)) ([711683c](https://github.com/momentohq/client-sdk-php/commit/711683c63afd7f50c5f532fae24b5ede4c8c8bfe))


### Miscellaneous

* add placeholders to avoid future merge conflicts ([#225](https://github.com/momentohq/client-sdk-php/issues/225)) ([d32c7e5](https://github.com/momentohq/client-sdk-php/commit/d32c7e5e18b3c8aac68d17c54d3860c16e23d512))
* correct `sorted set remove elements` mention in docstrings ([#228](https://github.com/momentohq/client-sdk-php/issues/228)) ([d8fbfca](https://github.com/momentohq/client-sdk-php/commit/d8fbfcaf5076133dc10a5274e30b5327d494c7fe))
* fix inconsistent comment ([#231](https://github.com/momentohq/client-sdk-php/issues/231)) ([91a18d8](https://github.com/momentohq/client-sdk-php/commit/91a18d86f2207fa47176350fb3de5b142648a0fb))
* make order in the sorted set functions more explicit ([#235](https://github.com/momentohq/client-sdk-php/issues/235)) ([702897c](https://github.com/momentohq/client-sdk-php/commit/702897c5d69ebbeba533ebe4955823ba228c9d38))
* remove the type from sorted set scores so they support ints ([#242](https://github.com/momentohq/client-sdk-php/issues/242)) ([c84adcc](https://github.com/momentohq/client-sdk-php/commit/c84adcca5ab05d728967b563355b63bfd76b7789))
* remove unnecessary tests and fix test warnings ([#236](https://github.com/momentohq/client-sdk-php/issues/236)) ([8d41a6e](https://github.com/momentohq/client-sdk-php/commit/8d41a6ec34a1ddf12af87e035ac5e8e71913aeaa))
* rename the ThrowsException tests to ReturnsError ([#239](https://github.com/momentohq/client-sdk-php/issues/239)) ([6e21636](https://github.com/momentohq/client-sdk-php/commit/6e2163625c0e2ce8a3ed6f6ff51d0382e053c2fe))
* support devcontainer development ([#220](https://github.com/momentohq/client-sdk-php/issues/220)) ([d0c9a85](https://github.com/momentohq/client-sdk-php/commit/d0c9a85d6369ec761447c4c264a5dd505baadab5))
* Update the documentation comply with the PHP license ([#240](https://github.com/momentohq/client-sdk-php/issues/240)) ([f40b7dd](https://github.com/momentohq/client-sdk-php/commit/f40b7dd67d12515acd82009f127fdb28a25ed3ec))

## [1.13.0](https://github.com/momentohq/client-sdk-php/compare/v1.12.0...v1.13.0) (2024-10-15)


### Features

* add getItemTtl api ([#214](https://github.com/momentohq/client-sdk-php/issues/214)) ([533a460](https://github.com/momentohq/client-sdk-php/commit/533a46075ff97510230ae6a717c4fbc71eac9429))
* add update ttl apis ([#216](https://github.com/momentohq/client-sdk-php/issues/216)) ([8c2086d](https://github.com/momentohq/client-sdk-php/commit/8c2086d1baf5ef7a6e544cd1b5acf73fc5b1e155))

## [1.12.0](https://github.com/momentohq/client-sdk-php/compare/v1.11.1...v1.12.0) (2024-09-25)


### Features

* add SetContainsElements API ([#209](https://github.com/momentohq/client-sdk-php/issues/209)) ([eec6083](https://github.com/momentohq/client-sdk-php/commit/eec6083305d82d45136889e522db5c61d922333d))


### Miscellaneous

* add Apache 2.0 license ([#207](https://github.com/momentohq/client-sdk-php/issues/207)) ([6faa79e](https://github.com/momentohq/client-sdk-php/commit/6faa79e6c4eac1ad4c5b9a0fde1068815349d272))
* add storage docs snippets ([#205](https://github.com/momentohq/client-sdk-php/issues/205)) ([1458202](https://github.com/momentohq/client-sdk-php/commit/14582029121383e5904673df8b82ce03fe6ef74a))
* rename TEST_AUTH_TOKEN to MOMENTO_API_KEY ([#208](https://github.com/momentohq/client-sdk-php/issues/208)) ([4b63c2f](https://github.com/momentohq/client-sdk-php/commit/4b63c2f19d831e097068a1d918c792a23978314f))
* Run CI on PHP 8.3 and 8.4, fix PHP 8.4 support, pin actions versions ([#211](https://github.com/momentohq/client-sdk-php/issues/211)) ([22d1dcb](https://github.com/momentohq/client-sdk-php/commit/22d1dcbc5986862f829d7fae8a0dd00a12ad6d74))

## [1.11.1](https://github.com/momentohq/client-sdk-php/compare/v1.11.0...v1.11.1) (2024-07-09)


### Miscellaneous

* update get responses ([#198](https://github.com/momentohq/client-sdk-php/issues/198)) ([18a185a](https://github.com/momentohq/client-sdk-php/commit/18a185a153422c710c451af0a96f676a57b63dc2))

## [1.11.0](https://github.com/momentohq/client-sdk-php/compare/v1.10.0...v1.11.0) (2024-07-08)


### Features

* one time headers ([#192](https://github.com/momentohq/client-sdk-php/issues/192)) ([e48a8d4](https://github.com/momentohq/client-sdk-php/commit/e48a8d4adc5ae7fb70a80ade2a53646372a785bd))


### Miscellaneous

* add release please workflow ([#199](https://github.com/momentohq/client-sdk-php/issues/199)) ([d0c6137](https://github.com/momentohq/client-sdk-php/commit/d0c6137dd707719d366be4f2ef474fcb42b90379))
* **deps-dev:** bump composer/composer from 2.7.1 to 2.7.7 ([#196](https://github.com/momentohq/client-sdk-php/issues/196)) ([c7dfa7d](https://github.com/momentohq/client-sdk-php/commit/c7dfa7da3d03a84ca01f180c974403d40dca8a7d))
* release 1.11.0 ([#201](https://github.com/momentohq/client-sdk-php/issues/201)) ([55fa70a](https://github.com/momentohq/client-sdk-php/commit/55fa70a571ef6e3bb7cbab6745aa53fc0fe6ec86))
* update metadata message signifying item not found ([#193](https://github.com/momentohq/client-sdk-php/issues/193)) ([e5ea030](https://github.com/momentohq/client-sdk-php/commit/e5ea0304c26d4c641a137c395e6201186691a816))
