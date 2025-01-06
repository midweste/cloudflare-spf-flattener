# Cloudflare SPF (Sender Policy Framework) Flattening/Splitting Library

# No tests yet - Use at your own risk!

Extension of the wonderful? SPF-LIB-FLATTENER by me (https://github.com/midweste/spf-lib-flattener) and SPF-LIB library by mlocati (https://github.com/mlocati/spf-lib)

This PHP library allows you to:

- retrieve the v=spfmaster spf record from Cloudflare
- flatten a spf record into ips addresses
- split a flattened spf record into primary and child spf records
- update the root spf record (v=spf a mx include:spf1.domain.com include:spf2.domain.com -all)
- create and update spf sub records (spf1.domain.com spf2.domain.com)

## Short introduction about Cloudflare SPF Flattener/Splitter

This library is meant to address the issue where more than 10 lookups are present in a SPF record. It will retrieve a source spf txt record that starts with v=spfmaster, flatten, and split that into 2048 character subdomain spf records with resolved ip addresses. Then it will update your primary record, and create any secondary records.

## Installation

You can install this library with Composer:

```sh
composer require midweste/cloudflare-spf-flattener
```

## Code Usage

### Flattening all account zones

```php
use CloudflareSpf\AccountFlattener;

require __DIR__ . '/../vendor/autoload.php';

$apiToken = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$flattener = new AccountFlattener($apiToken);
$flattener->addExclude('excluded-domain.com')->flatten();

```

### Flattening an account zone

```php
use CloudflareSpf\ZoneFlattener;

require __DIR__ . '/../vendor/autoload.php';

$apiToken = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$flattener = new ZoneFlattener('example.com', $apiToken);
$flattener->flatten();

```

## Cli Usage

### Flattening all account zones

```bash
php bin/cf-spf-flattener account:flatten --api-token=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

```bash
php bin/cf-spf-flattener account:flatten --cloudflare-json=/path/to/credential/file.json
```

### Flattening an account zone

```bash
php bin/cf-spf-flattener zone:flatten example.com --api-token=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

```bash
php bin/cf-spf-flattener zone:flatten example.com --cloudflare-json=/path/to/credential/file.json
```

## Do you want to really say thank you?

You can offer the original author of Spf-Lib that did all the heavy lifting (mlocati) a [monthly coffee](https://github.com/sponsors/mlocati) or a [one-time coffee](https://paypal.me/mlocati) :wink:
