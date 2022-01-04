# Changelog
All notable changes to this project will be documented in this file.


## [2.2.1] - 2022-01-04
### Fixed
- The installation process is always indicated failure the first time while the module is installed
- Tokenization option is visible for Guest on Storefront
- Payments fail if the 3DSecure authorization is required


## [2.2.0] - 2021-10-19
### Changed
- Branding Update
- Add Embedded Payment Option

### Fixed
- Remove unnecessary property to make module compatible with the new version of PrestaShop
- Modal Payment Option doesn't work on the new version of PrestaShop
- Payment details are missed for the Order in the Admin Panel


## [2.1.0] - 2020-10-23
### Changed
- Authorization + Capture modes in addition to Payment
- Capture and Reverse (void) operation in admin
- Updated Simplify SDK
- Refactored dist creation
- Cleanup
- Making changes to make plugin function with non ISO-8859-1 Characters (Greek, Arabic...) by processing Hosted payment fields through translit


## [2.0.1]
### Fixed
- Fixing some plugin links


## [2.0.0]
### Changed
- Major release to remove the standard payment form integration. Only hosted payments can now be made from the plugin.


## [1.1.0]
### Changed
- Upgrading plugin for Prestashop versions 1.7+


## [1.0.12]
### Changed
-This is the simplify commerce payment plugin for Prestashop versions 1.4-1.6 inclusive.
-This will not work on prestashop 1.7+
-Please use the other plugin for that version

