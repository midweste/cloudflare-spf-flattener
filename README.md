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
$account = new AccountFlattener($apiToken);
$account->addExclude('excluded-domain.com')->addOrder('primary-domain.com')->flatten();

```

### Flattening an account zone

```php
use CloudflareSpf\ZoneFlattener;

require __DIR__ . '/../vendor/autoload.php';

$apiToken = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$zone = new ZoneFlattener('example.com', $apiToken);
$zone->flatten();

```

## Cli Usage

### Configuration

Copy the settings.json.example and fill in the stubs

-Most settings pretty self explanatory
-Ordering is there if you need to set a specific order (one domain uses include of another). Haven't tested the TTL to see if they if changes made to the first domain are reflected on the other quite yet, that't the point though.
-Email log_level is the minimum level you want notification for, generally error is fine. Change to "debug" to be notified every time.
-SSL is one of: none, tls, ssl (uses phpmailer)

```json
{
  "api_token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "zones": {
    "excluded": ["excluded-one.com", "excluded-two.com"],
    "order": ["first-domain.com", "second-domain.com"]
  },
  "notifications": {
    "enabled": true,
    "channels": ["email"],
    "settings": {
      "email": {
        "log_level": "error",
        "host": "smtp.mailserver.org",
        "username": "smtp@username.com",
        "password": "xxxxxxxxxxxxxxxxx",
        "port": 587,
        "ssl": "tls",
        "from_email": "from@domain.com",
        "to_email": "to@domain.com"
      }
    }
  }
}
```

### Flattening all account zones

```bash
php bin/cloudflare-spf-flattener account:flatten /path/to/settings.json
```

### Flattening an account zone

```bash
php bin/cloudflare-spf-flattener zone:flatten /path/to/settings.json example.com
```

### Output of CLI

```
NOTICE: Start account spf flattening
NOTICE: Started splitting SPF records for first-domain.com
INFO: Adding/Updating record for spf1.first-domain.com
DEBUG: No change needed for spf1.first-domain.com
INFO: Adding/Updating record for spf2.first-domain.com
DEBUG: No change needed for spf2.first-domain.com
INFO: Adding/Updating record for first-domain.com
DEBUG: No change needed for first-domain.com
NOTICE: Finished splitting SPF records for first-domain.com
NOTICE: Started splitting SPF records for second-domain.com
INFO: Adding/Updating record for spf1.second-domain.com
DEBUG: No change needed for spf1.second-domain.com
INFO: Adding/Updating record for spf2.second-domain.com
DEBUG: No change needed for spf2.second-domain.com
INFO: Adding/Updating record for second-domain.com
DEBUG: No change needed for second-domain.com
NOTICE: Finished splitting SPF records for first-domain.com
WARNING: Excluded excluded-one.com.  Skipping
WARNING: Excluded excluded-two.com.  Skipping
NOTICE: Finished account spf flattening
```

## Do you want to really say thank you?

You can offer the original author of Spf-Lib that did all the heavy lifting (mlocati) a [monthly coffee](https://github.com/sponsors/mlocati) or a [one-time coffee](https://paypal.me/mlocati) :wink:
