![Packagist](https://img.shields.io/packagist/l/airtimerewards/ar-connect-sdk.svg)
![Packagist](https://img.shields.io/packagist/v/airtimerewards/ar-connect-sdk.svg)
[![Build Status](https://travis-ci.org/airtimerewards/ar-connect-php-sdk.svg?branch=master)](https://travis-ci.org/airtimerewards/ar-connect-php-sdk)

# AR Connect SDK

This package offers a client for interacting with [AR Connect](https://www.airtimerewards.co.uk/connect).

## 1. Requirements

This SDK requires PHP 7.1 or later. Additionally you must be registered with 
[AR Connect](https://www.airtimerewards.co.uk/connect) to be able to use an API. Finally, you will need an environment 
set up and an API key for that environment.

## 2. Installation

The recommended installation is to use [composer](https://www.getcomposer.com), and run the command:

```bash
$ composer require airtimerewards/ar-connect-sdk
```

## 3. Usage

The AR Connect client provides methods for interacting with the AR Connect API.

### 3.1 Instantiating a client

To instantiate a client, use the factory method and pass in your environment ID and API key.

**Important:** The API key must be for the environment that has been specified. Errors will occur if they do not match.

```php
<?php

use AirtimeRewards\ARConnect\ARConnectClient;

$client = ARConnectClient::createClient('api-key', 'environment-id', $logger);
```

### 3.2 Retrieving Data to Make a Credit

Before making a credit you need to find some information about the account for which the credit is being applied: the
mobile phone network and whether it's a pay-as-you-go (prepaid) or a monthly contract (postpaid). This can be done by
calling the `getNetworks()` method. This method accepts an optional MSISDN (international standard mobile phone 
number with the country-code prefix) `getNetworks('447700900000')` which when passed, will return only networks 
applicable to that mobile account.

```php
<?php 

use AirtimeRewards\ARConnect\ARConnectClient;

$client = ARConnectClient::createClient('api-key', 'environment-id', $logger);

$networks = $client->getNetworks(); // returns a collection of networks
$filteredNetworks = $client->getNetworks('447700900000'); // returns a collection of networks for UK mobile number 07700 900 000
```

Once the network has been selected, you can get information about what credit types are supported for this network. The 
credit types contain information about the minimum, maximum and increments of credit values available.

```php
<?php 

use AirtimeRewards\ARConnect\ARConnectClient;

$client = ARConnectClient::createClient('api-key', 'environment-id', $logger);
$creditTypes = $client->getCreditTypesForNetwork($network);
```

All the information required to apply a credit has now been collected.

### 3.3 Applying a credit

A credit can be applied by calling the `createCredit()` method with the following information:

 * MSISDN
 * Network
 * Subscription type (`Credit::SUBSCRIPTION_TYPE_POSTPAID` or `Credit::SUBSCRIPTION_TYPE_PREPAID`)
 * The value of the credit (an instance of `Money\Money`, see [PHP Money](http://moneyphp.org))
 * Whether an SMS should be sent to the account once credited
 * An optional unique reference. This is highly recommended as it will prevent a credit being accidentally applied 
 multiple times.

```php
<?php 

use AirtimeRewards\ARConnect\ARConnectClient;
use AirtimeRewards\ARConnect\Credit;
use Money\Money;
use Money\Currency;

$client = ARConnectClient::createClient('api-key', 'environment-id', $logger);
$creditValue = new Money(1000, new Currency('GBP')); // £10 of credit
$credit = $client->createCredit(
    '447700900000',
    $network,
    Credit::SUBSCRIPTION_TYPE_POSTPAID,
    $creditValue,
    true,
    'client-reference'
);
```

An instance of `AirtimeRewards\ARConnect\Credit` will be returned. It is recommended that the ID of the credit is 
stored so that it can be used for reference and retrieval at a future date (e.g. for checking the latest status of 
the credit).
