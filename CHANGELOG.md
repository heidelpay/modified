# Release Notes - heidelpay extension for modified

## v17.3.30

### Added
- new payment method invoice secured
- new payment method direct debit secured
- add doc blocks to file and functions

### Changed
- enforce code style
- add composer support   
#### giropay
- input fields for iban and bic in store front are not longer required
#### sofort
- input fields for iban and bic in store front are not longer required. Please contact our support to switch your sofort project settings.
### Credit card
- fix possible include injection in after registration action   

### Fixed

### Removed
### direct debit
- bic is not longer necessary for the 23 SEPA countries
- account number and bank id will not longer be supported
 