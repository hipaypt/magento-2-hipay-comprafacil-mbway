# MB WAY payment gateway for Magento 2

## Module Configuration

### API credentials

HiPay API production or sandbox account credentials for each currency:
   - HiPay Comprafacil merchant login
   - HiPay Comprafacil merchant password
   - HiPay Comprafacil Entity
   - Category id
	
### Setup
    
  - Enabled: enable or disable extension
  - Sandbox: enable or disable sandbox account
  - Account credentials for sandbox or production account
  - Debug: log payment info
  - Entity: entity for the sandbox or production account
  - Category id: merchant category


## Requirements
  - SOAP extension


## Installation

Run
```console
composer require hipaypt/magento-2-hipay-comprafacil-mbway
```
and then
```console
bin/magento module:enable --clear-static-content Hipay_HipayMbwayGateway
bin/magento setup:upgrade
bin/magento cache:clean
```

To update
```console
composer update hipaypt/magento-2-hipay-comprafacil-mbway
```
and then
```console
bin/magento setup:static-content:deploy
bin/magento setup:upgrade
bin/magento cache:clean
```

Version 1.0.2