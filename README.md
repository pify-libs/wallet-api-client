# Wallet API Client for pify.cc

[![Latest Version](https://img.shields.io/packagist/v/pify/wallet-api-client.svg)](https://packagist.org/packages/pify/wallet-api-client)
[![PHP Version](https://img.shields.io/packagist/php-v/pify/wallet-api-client)](https://packagist.org/packages/pify/wallet-api-client)
[![License](https://img.shields.io/packagist/l/pify/wallet-api-client.svg)](https://packagist.org/packages/pify/wallet-api-client)

PHP client for interacting with Pify.cc Wallet API.

## Installation

You can install the package via Composer:

## bash
composer require pify/wallet-api-client

## Usage
```
require_once 'vendor/autoload.php';

use Pify\WalletApiClient\WalletApiClient;

$apiToken = 'your_api_token_here';
$client = new WalletApiClient($apiToken);
// Get balance
$balance = $client->getBalance();
if ($balance) {
    print_r($balance);
} else {
    echo "Error: " . $client->getLastError() . "\n";
}

// More examples in the documentation below.
```
## API Methods
```Get Balance
php
$result = $client->getBalance();
```
Get Transaction History
```
php
// All user history
$result = $client->getHistory();
// Specific wallet with filters
$result = $client->getHistory([
    'wallet_id' => 123,
    'page' => 1,
    'page_size' => 50,
    'filters' => [
        'operation_type' => 'deposit',
        'date_from' => '2024-01-01'
    ]
]);
```
Internal Transfer
```php
$result = $client->transfer(
    $fromWalletId = 123,
    $toIdentifier = 'W12345ABC', // wallet ID or address
    $amount = 100.50,
    $comment = 'Payment for services'
);
```
External Transfer
```
php
$result = $client->transferExternal(
    $fromWalletId = 123,
    $toAddress = 'TXYZ123...', // external crypto address
    $amount = 50.0,
    $comment = 'Withdrawal'
);
```
Check Transfer Possibility
```
php
$result = $client->checkTransfer(123, 100.0);
if ($result && $result['data']['can_transfer']) {
    echo "Transfer is possible\n";
}
```
Get Statistics
```
php
$result = $client->getStatistics('month'); // day, week, month, year
Get Wallet Info
php
$result = $client->getWalletInfo(123);
```
Error Handling
```
php
$result = $client->getBalance();

if (!$result) {
    $error = $client->getLastError();
    $response = $client->getLastResponse();

    echo "Error: {$error}\n";
    echo "HTTP Code: {$response['http_code']}\n";

    // Log error
    error_log("Wallet API Error: {$error}");
}
```
## Configuration
Custom Base URL
```
php
$client->setBaseUrl('https://api.pify.cc');
Custom Timeout
php
$client->setTimeout(60); // 60 seconds
```
Requirements
PHP 7.4 or higher
