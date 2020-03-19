# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [4.4.1]
- Fix RocketTheme integration

## [4.4.0]
- Version bump

## [4.3.3]
- Added PHP 7.4 test to Travis
- Added `stream_set_option` to support PHP 7.4

## [4.3.2]
- Added `ResourceLocator::registerSharedStream` method

## [4.3.1]
- Added `__tostring` to ResourceInterface
- Added `__invoke` to ResourceLocatorInterface
- `ResourceLocationInterface::setPath` don't accept a `null` value anymore (produced an error anyway)
- Added proper PHP7 type hints
- Misc code quality and docblock fix

## [4.3.0]
- Dropping support for PHP 5.6 & 7.0
- Updated rockettheme/toolbox to 1.4.x
- Updated Laravel Illuminate packages to 5.8
- Updated PHPUnit to 7.5

## [4.2.3]
 - Added `sort` param to `listResources` method [#4]

## [4.2.2]
 - Normalize base path to fix Windows paths separator issue
 - Added AppVeyor config for Windows based CI

## [4.2.1]
 - Added `ResourceInterface`, `ResourceLocationInterface`, `ResourceLocatorInterface` & `ResourceStreamInterface`

## 4.2.0
 - Initial Release

<!--
## [Unreleased]

### Added

### Changed

### Deprecated

### Removed

### Fixed

### Security
-->

[4.4.1]: https://github.com/userfrosting/uniformresourcelocator/compare/4.4.0...4.4.1
[4.4.0]: https://github.com/userfrosting/uniformresourcelocator/compare/4.3.3...4.4.0
[4.3.3]: https://github.com/userfrosting/uniformresourcelocator/compare/4.3.2...4.3.3
[4.3.2]: https://github.com/userfrosting/uniformresourcelocator/compare/4.3.1...4.3.2
[4.3.1]: https://github.com/userfrosting/uniformresourcelocator/compare/4.3.0...4.3.1
[4.3.0]: https://github.com/userfrosting/uniformresourcelocator/compare/4.2.3...4.3.0
[4.2.3]: https://github.com/userfrosting/uniformresourcelocator/compare/4.2.2...4.2.3
[4.2.2]: https://github.com/userfrosting/uniformresourcelocator/compare/4.2.1...4.2.2
[4.2.1]: https://github.com/userfrosting/uniformresourcelocator/compare/4.2.0...4.2.1
[#4]: https://github.com/userfrosting/UniformResourceLocator/issues/4
