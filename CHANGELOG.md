# Release Notes - heidelpay extension for modified

## v18.3.14

### Added
- basket api for secured invoice and direct debit

### Changed
- secured invoice is no longer available after user was denied by insurance provider 
for the time of the actual session.
- error message is replaced by an error code that is then translated by a message-code-mapper
- sofortueberweisung was renamed sofort
- Heidelberger Payment GmbH was renamed heidelpay GmbH

### Removed
- Billsafe

## v17.4.7

### Added
- new payment method invoice secured
- new payment method direct debit secured
- add doc blocks to file and functions
- customer address has to be equal for all b2c payment methods
- disable b2c payment methods when company is set


### Changed
- enforce code style
- add composer support
- replace mysql with mysqli api, required for php7 support 
#### giropay
- input fields for iban and bic in store front are not longer required
#### sofort
- input fields for iban and bic in store front are not longer required. Please contact our support to switch your sofort project settings.
### Credit card
- fix possible include injection in after registration action
- escape string on after registration action

### Fixed

### Removed
### direct debit
- bic is not longer necessary for the 23 SEPA countries
- account number and bank id will not longer be supported