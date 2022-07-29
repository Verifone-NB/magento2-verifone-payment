# Change Log
All notable changes to this project will be documented in this file.

## [0.2.1] 2022.07.29
### Fixed
- Problem with Logger method declaration

## [0.2.0] 2022.07.27
### Added
- Support for Magento 2.4.4

## [0.1.14] 2020.09.17
### Added
- Support for PHP 7.4

## [0.1.13] 2020.09.02
### Fixed
- Wrong label for Enterpay payment method

## [0.1.12] 2019.11.11
### Added
- Support for PHP 7.3

### Fixed
- Problem with undefined index, when no payment method selected

## [0.1.11] 2019.11.07
### Fixed
- Problem with wrong version of the Core library

## [0.1.10] 2019.11.01
### Changed
- Add billing address into payment request - PSD/2 regulation

### Removed
- Possibility to pay with saved credit card by S2S request - PSD/2 regulation

### Fixed
- Problem with a return after add card request in Magento >= 2.3.2

## [0.1.9] 2019.10.25
### Fixed
- Problem with a return after pay in Magento >= 2.3.2

## [0.1.8] 2019.08.18
### Fixed
- Problem with wrong object type (with PHP 7.2.x)

## [0.1.7] 2019.06.02
### Added
- Support for PHP 7.2.x

### Removed
- Support for PHP 7.0.x

### Fixed
- Problem with missing return type in Magento 2.3.1

## [0.1.6] 2019.05.11
### Changed
- Updated required version of Core library in the composer.json

## [0.1.5] 2019.03.11
### Changed
- Small improvements for create the default payment groups

### Fixed
- Typo in discount product name
- Missing discount product when combined basket items option is enabled

## [0.1.4] 2019.02.11
### Fixed
- Fix for Magento 2.3 CsrfValidator and backwards-compatibility to prior Magento 2 versions
- Fix for refresh saved payment methods page.

## [0.1.3] 2019.01.30
### Changed
- Small changes in labels and descriptions for in the configuration form
- Updated translations

### Fixed
- Compatibility with Magento 2.3 - remove tinymce editor from the group description field

## [0.1.2] 2019.01.16
### Added
- Logic for prevent against process 2 payment responses at same time.

### Changed
- Hidden selector for payment method when just All In One available
- Change name for All in One payment method (typo)
- Fetch saved payment method just when action is allowed in configuration

### Fixed
- Payment additional_information is overwritten when adding a new order transaction
- Exception when no payment method select for card group

## [0.1.1] 2018.12.04
### Added
- Possibility to configure simple and advanced mode for key handling
- New fields in configuration
- Possibility to generate new keys for test and live
- Possibility to store keys in database

### Changed
- Removed a preference and replaced it with an around plugin to preserve existing plugins.
- Logic for fetch keys - from a file and a database
- Update translation file

### Fixed
- One click request address data
- Display "Check payment status" just for pending orders
- Set payment method selected in payment service, not in shop.

## [0.1.0] 2018.10.12
### Added
- New payment methods: MobilePay, Vipps, MasterPass
- New field to the configuration: directory for stored keys
- Possibility to display summary for configuration
- Default payment service live key
- Customer delivery address into payment request

### Changed
- Configuration values for shop and payment service keys. 
  This change requires update configuration for fields with key path and filename. 
  
### Fixed
- Problem with rounding prices.
- Problem with creates a combined item.

## [0.0.28] 2018.09.14
### Fixed
- Set unique name for JavaScript function for generate keys

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
