![Logo](https://dev.heidelpay.de/devHeidelpay_400_180.jpg)

# Welcome to the heidelpay payment extension for xtcommerce 3 / modified commerce



## Currently supported payment methods:

* credit card
* debit card
* prepayment
* Sofort
* PayPal
* direct debit
* iDeal
* Giropay
* EPS
* invoice

### SYSTEM REQUIREMENTS

The extension requires PHP 5.6;

## LICENSE

You can find a copy of this license in [LICENSE.txt](LICENSE.txt).


## Installation with composer

  "scripts": {
    "post-install-cmd": [
      "SlowProg\\CopyFile\\ScriptHandler::copy"
    ],
    "post-update-cmd": [
      "SlowProg\\CopyFile\\ScriptHandler::copy"
    ]
  },

  "extra": {
    "copy-file": {
      "vendor/heidelpay/modified/admin/includes/modules/export/": "admin/include/modules/export/",
      "vendor/heidelpay/modified/images/ladebalken.gif": "images/ladebalken.gif",
      "vendor/heidelpay/modified/includes/classes/class.heidelpay.php": "includes/classes/class.heidelpay.php",
      "vendor/heidelpay/modified/includes/modules/payment/": "includes/modules/payment/",
      "vendor/heidelpay/modified/lang/english/modules/payment/": "lang/english/modules/payment/",
      "vendor/heidelpay/modified/lang/german/modules/payment/": "lang/german/modules/payment/",
      "vendor/heidelpay/modified/heidelpay_3dsecure.php": "heidelpay_3dsecure.php",
      "vendor/heidelpay/modified/heidelpay_3dsecure_return.php": "heidelpay_3dsecure_return.php",
      "vendor/heidelpay/modified/heidelpay_after_register.php": "heidelpay_after_register.php",
      "vendor/heidelpay/modified/heidelpay_checkout_iframe.php": "heidelpay_checkout_iframe.php",
      "vendor/heidelpay/modified/heidelpay_gm_checkout_iframe.php": "heidelpay_gm_checkout_iframe.php",
      "vendor/heidelpay/modified/heidelpay_iframe.php": "heidelpay_iframe.php",
      "vendor/heidelpay/modified/heidelpay_redirect.php": "heidelpay_redirect.php",
      "vendor/heidelpay/modified/heidelpay_reg_style.css": "heidelpay_reg_style.css",
      "vendor/heidelpay/modified/heidelpay_style.css": "heidelpay_style.css",
      "vendor/heidelpay/modified/heidelpay_success.inc.php": "heidelpay_success.inc.php",
      "vendor/heidelpay/modified/heidelpay_success.php": "heidelpay_success.php",
      "vendor/heidelpay/modified/LICENSE.txt": "includes/external/heidelpay/",
      "vendor/heidelpay/modified/README.md": "includes/external/heidelpay/"
    }
  },