## [2.0.1](https://github.com/good-php/reflection/compare/v2.0.0...v2.0.1) (2025-09-07)


### Bug Fixes

* Reflecting iterable implementing classes ([#14](https://github.com/good-php/reflection/issues/14)) ([e218e0b](https://github.com/good-php/reflection/commit/e218e0bfd177fd2b79ef09a3b7c3432701b3793a))

# [2.0.0](https://github.com/good-php/reflection/compare/v1.0.2...v2.0.0) (2025-07-08)


### chore

* Release 2.x ([#12](https://github.com/good-php/reflection/issues/12)) ([024d861](https://github.com/good-php/reflection/commit/024d861b281703e0bc4045cb2043a0c66cb24d5d))


### Features

* Update deps, remove dependencies ([#11](https://github.com/good-php/reflection/issues/11)) ([c77d054](https://github.com/good-php/reflection/commit/c77d05491e574c756080a8d60ff96ab073a1b419))


### BREAKING CHANGES

* removed Collections

# [2.0.0](https://github.com/good-php/reflection/compare/v1.0.2...v2.0.0) (2025-07-08)


### Features

* Update deps, remove dependencies ([#11](https://github.com/good-php/reflection/issues/11)) ([c77d054](https://github.com/good-php/reflection/commit/c77d05491e574c756080a8d60ff96ab073a1b419))

## [1.0.2](https://github.com/good-php/reflection/compare/v1.0.1...v1.0.2) (2024-05-15)


### Bug Fixes

* Cache namespace ([#9](https://github.com/good-php/reflection/issues/9)) ([4599204](https://github.com/good-php/reflection/commit/45992046c2a469f99de4d5ca8e648e718b9af480))

## [1.0.1](https://github.com/good-php/reflection/compare/v1.0.0...v1.0.1) (2024-05-15)


### Bug Fixes

* Use namespaced cache per reflection version to avoid having to manually clear it ([#8](https://github.com/good-php/reflection/issues/8)) ([b287bb5](https://github.com/good-php/reflection/commit/b287bb5018aff7d8ac3e1a378dac9e598957eb27))

# 1.0.0 (2024-05-14)


### Bug Fixes

* Add Mozart Assert dependency ([1e4a832](https://github.com/good-php/reflection/commit/1e4a83293306e4d6b53ea3a49cb0fcd78844dc0b))
* Broken list type ([#5](https://github.com/good-php/reflection/issues/5)) ([458c631](https://github.com/good-php/reflection/commit/458c631173bbe6a49e8fe9fbcde13a854a10108b))
* ClassReflection::constructor() always returning null ([1cdf1d2](https://github.com/good-php/reflection/commit/1cdf1d2e13d461be23cb8f5e856c37cea52a19e9))
* Dependency requirement ([#1](https://github.com/good-php/reflection/issues/1)) ([dac6995](https://github.com/good-php/reflection/commit/dac6995bde28956dddb8f4e00d130a0653aca6b5))
* Handling of `parent` type ([14eb9e0](https://github.com/good-php/reflection/commit/14eb9e0a1c4286b68be1a50c649532682f2690f8))
* Handling of `static` type and nested interface inheritance ([ceb5ff2](https://github.com/good-php/reflection/commit/ceb5ff2233f872d1af60fa5ea8b9004ededeba09))
* Imports of non-namespaces class-likes in PHPDocs ([7b25bde](https://github.com/good-php/reflection/commit/7b25bded98f1a4d93f86f226b4a3925f5407a8fe))
* NamedType::__toString() ([629816d](https://github.com/good-php/reflection/commit/629816dbbd65492f1805f9a7dfdd1f2b9ffa92dc))
* PHPStan ([a22650e](https://github.com/good-php/reflection/commit/a22650e0e4dacc918038b16e09b825f08bb500f6))
* Release master ([#7](https://github.com/good-php/reflection/issues/7)) ([7528dcc](https://github.com/good-php/reflection/commit/7528dccebfd2be998f24dda3421d5002d528df24))
* Release new version ([#3](https://github.com/good-php/reflection/issues/3)) ([0969c8e](https://github.com/good-php/reflection/commit/0969c8e44e338f680ca70a04fa09fba322b008f2))
* Strict setting property / calling method broken ([19af6dd](https://github.com/good-php/reflection/commit/19af6dd6c3109cf003026432a596699e4292241a))
* Update nikic/parser to 5.x ([#4](https://github.com/good-php/reflection/issues/4)) ([3226376](https://github.com/good-php/reflection/commit/3226376d071af03e5a3728df3f2f3778c984c3f4))


### Features

* Add ArrayAttributes so you can initialize them manually ([4c746b5](https://github.com/good-php/reflection/commit/4c746b599f60f6f0f7eea2b9cd7140f2d624fc1d))
* Add some QoL functions ([bffe983](https://github.com/good-php/reflection/commit/bffe983c113d6ade8f7008b729b8091a0da09822))
* Allow empty Attributes bag ([6b177f9](https://github.com/good-php/reflection/commit/6b177f94753b817ed163cc6cdb914597c9f70cce))
* Allow more cache configuration in ReflectorBuilder ([9c6044f](https://github.com/good-php/reflection/commit/9c6044f2edee7a837a6c71cc9d1c5a3a7ab619e2))
* Attributes has any and __toString ([7a85835](https://github.com/good-php/reflection/commit/7a85835a896dd384157df3c8f99aa7c3f09ce4ba))
* Attributes::allEqual() ([380a762](https://github.com/good-php/reflection/commit/380a762aab9637ed4a414916575741021b376759))
* ClassReflection::newInstanceWithoutConstructor() ([ae089fe](https://github.com/good-php/reflection/commit/ae089fed841d3fdda2c9d58ecdd2e933df0786ac))
* FunctionParameterReflection::defaultValue() ([cdd3f49](https://github.com/good-php/reflection/commit/cdd3f491e6daf54c67c0f6a238e336e347a7bfb3))
* Initial release ([e853f6e](https://github.com/good-php/reflection/commit/e853f6ed5e548f83c630ecd001b5419e4a60cc37))
* invokeStrict/setStrict ([346d303](https://github.com/good-php/reflection/commit/346d3035c0b1db6807a72bb42b5e0f2a0e601996))
* PropertyReflection::defaultValue() ([73218ff](https://github.com/good-php/reflection/commit/73218ffa98ebc92ca9fe2f21b41eece29e2a43fc))
* PropertyReflection::hasDefaultValue() ([832577f](https://github.com/good-php/reflection/commit/832577fac9078c2a63fe086114a62cf993eb412e))
* PropertyReflection::promotedParameter() ([953eea6](https://github.com/good-php/reflection/commit/953eea65c1cc9a4e70331fb8698c7b5991fb3bd0))
* Trait generics, aliases and precedence full support ([74c3892](https://github.com/good-php/reflection/commit/74c38920f7f78b18aea8665c53859554c0ecb8dc))


### Performance Improvements

* Add benchmark results ([a206269](https://github.com/good-php/reflection/commit/a206269e4c8f9fdde74ebcaa1e9a75696ccf4339))
* Improve performance slightly, add benchmarks ([382d4b6](https://github.com/good-php/reflection/commit/382d4b6fdc711cc073b8a33f8bcad71b24ac6b3d))

# [1.0.0-alpha.5](https://github.com/good-php/reflection/compare/v1.0.0-alpha.4...v1.0.0-alpha.5) (2024-05-14)


### Bug Fixes

* Broken list type ([#5](https://github.com/good-php/reflection/issues/5)) ([458c631](https://github.com/good-php/reflection/commit/458c631173bbe6a49e8fe9fbcde13a854a10108b))

# [1.0.0-alpha.4](https://github.com/good-php/reflection/compare/v1.0.0-alpha.3...v1.0.0-alpha.4) (2024-03-08)


### Bug Fixes

* Update nikic/parser to 5.x ([#4](https://github.com/good-php/reflection/issues/4)) ([3226376](https://github.com/good-php/reflection/commit/3226376d071af03e5a3728df3f2f3778c984c3f4))

# [1.0.0-alpha.3](https://github.com/good-php/reflection/compare/v1.0.0-alpha.2...v1.0.0-alpha.3) (2024-03-08)


### Bug Fixes

* Release new version ([#3](https://github.com/good-php/reflection/issues/3)) ([0969c8e](https://github.com/good-php/reflection/commit/0969c8e44e338f680ca70a04fa09fba322b008f2))

# [1.0.0-alpha.2](https://github.com/good-php/reflection/compare/v1.0.0-alpha.1...v1.0.0-alpha.2) (2024-01-15)


### Bug Fixes

* Dependency requirement ([#1](https://github.com/good-php/reflection/issues/1)) ([dac6995](https://github.com/good-php/reflection/commit/dac6995bde28956dddb8f4e00d130a0653aca6b5))

# 1.0.0-alpha.1 (2023-11-03)


### Bug Fixes

* Add Mozart Assert dependency ([1e4a832](https://github.com/good-php/reflection/commit/1e4a83293306e4d6b53ea3a49cb0fcd78844dc0b))
* ClassReflection::constructor() always returning null ([1cdf1d2](https://github.com/good-php/reflection/commit/1cdf1d2e13d461be23cb8f5e856c37cea52a19e9))
* Handling of `parent` type ([14eb9e0](https://github.com/good-php/reflection/commit/14eb9e0a1c4286b68be1a50c649532682f2690f8))
* Handling of `static` type and nested interface inheritance ([ceb5ff2](https://github.com/good-php/reflection/commit/ceb5ff2233f872d1af60fa5ea8b9004ededeba09))
* Imports of non-namespaces class-likes in PHPDocs ([7b25bde](https://github.com/good-php/reflection/commit/7b25bded98f1a4d93f86f226b4a3925f5407a8fe))
* NamedType::__toString() ([629816d](https://github.com/good-php/reflection/commit/629816dbbd65492f1805f9a7dfdd1f2b9ffa92dc))
* PHPStan ([a22650e](https://github.com/good-php/reflection/commit/a22650e0e4dacc918038b16e09b825f08bb500f6))
* Strict setting property / calling method broken ([19af6dd](https://github.com/good-php/reflection/commit/19af6dd6c3109cf003026432a596699e4292241a))


### Features

* Add ArrayAttributes so you can initialize them manually ([4c746b5](https://github.com/good-php/reflection/commit/4c746b599f60f6f0f7eea2b9cd7140f2d624fc1d))
* Add some QoL functions ([bffe983](https://github.com/good-php/reflection/commit/bffe983c113d6ade8f7008b729b8091a0da09822))
* Allow empty Attributes bag ([6b177f9](https://github.com/good-php/reflection/commit/6b177f94753b817ed163cc6cdb914597c9f70cce))
* Allow more cache configuration in ReflectorBuilder ([9c6044f](https://github.com/good-php/reflection/commit/9c6044f2edee7a837a6c71cc9d1c5a3a7ab619e2))
* Attributes has any and __toString ([7a85835](https://github.com/good-php/reflection/commit/7a85835a896dd384157df3c8f99aa7c3f09ce4ba))
* Attributes::allEqual() ([380a762](https://github.com/good-php/reflection/commit/380a762aab9637ed4a414916575741021b376759))
* ClassReflection::newInstanceWithoutConstructor() ([ae089fe](https://github.com/good-php/reflection/commit/ae089fed841d3fdda2c9d58ecdd2e933df0786ac))
* FunctionParameterReflection::defaultValue() ([cdd3f49](https://github.com/good-php/reflection/commit/cdd3f491e6daf54c67c0f6a238e336e347a7bfb3))
* Initial release ([e853f6e](https://github.com/good-php/reflection/commit/e853f6ed5e548f83c630ecd001b5419e4a60cc37))
* invokeStrict/setStrict ([346d303](https://github.com/good-php/reflection/commit/346d3035c0b1db6807a72bb42b5e0f2a0e601996))
* PropertyReflection::defaultValue() ([73218ff](https://github.com/good-php/reflection/commit/73218ffa98ebc92ca9fe2f21b41eece29e2a43fc))
* PropertyReflection::hasDefaultValue() ([832577f](https://github.com/good-php/reflection/commit/832577fac9078c2a63fe086114a62cf993eb412e))
* PropertyReflection::promotedParameter() ([953eea6](https://github.com/good-php/reflection/commit/953eea65c1cc9a4e70331fb8698c7b5991fb3bd0))
* Trait generics, aliases and precedence full support ([74c3892](https://github.com/good-php/reflection/commit/74c38920f7f78b18aea8665c53859554c0ecb8dc))


### Performance Improvements

* Add benchmark results ([a206269](https://github.com/good-php/reflection/commit/a206269e4c8f9fdde74ebcaa1e9a75696ccf4339))
* Improve performance slightly, add benchmarks ([382d4b6](https://github.com/good-php/reflection/commit/382d4b6fdc711cc073b8a33f8bcad71b24ac6b3d))
