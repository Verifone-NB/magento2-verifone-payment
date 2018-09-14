# Change Log
All notable changes to this project will be documented in this file.

## [0.0.27] 2018.09.14
### Added
- This CHANGELOG file
- New payment method: AfterPay (Invoice)
- Functionality for generate new security keys

### Changed
- Add product into refund requests
- Update translations

### Fixed
- Increase size of payment method select in admin panel 
- Run additional validators before placing the order

## [0.0.26] 2018.08.28
### Added
- Logic for fetch default file path for test env

## [0.0.25] 2018.08.23
### Added
- Create command for recreate payments configuration

## [0.0.24] 2018.08.22
### Fixed
- Fixed problem with unserialize config value for payments configuration

## [0.0.23] 2018.08.21
### Fixed
- Fixed problem with "the CDATA comments in adminhtml templates break minified html"

## [0.0.22] 2018.08.15
### Changed
- Update setup script - fix a problem with duplicate payment methods

## [0.0.21] 2018.07.18
### Fixed
- Fixed demo merchant configuration

## [0.0.20] 2018.07.14
### Added
- Implement functionality to use custom configuration for style code field
- Functionality to set test and live account configuration at same time

## [0.0.19] 2018.04.28
### Changed
- Remove unnecessary comment from configuration xml file

## [0.0.18] 2018.03.22
### Added
- New payment methods: PayPal, Swish, Siirto, Collector Lasku

## [0.0.17] 2018.02.07
### Added
- RSA keys for test demo merchant account

## [0.0.16] 2018.01.12
### Fixed
- Compatibility with Magento in version >= 2.1.0

## [0.0.15] 2017.10.19
### Added
- New PHP version `~7.1.0` in composer.json

## [0.0.14] 2017.10.04
### Added
- Compatibility with Magento in version >= 2.2.0
- Script for convert payment group type

### Changed
- Data type for stored payment groups from serialize() into json_encode()