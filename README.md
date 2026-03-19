# MyAdmin CloudLinux Licensing Plugin

[![Tests](https://github.com/detain/myadmin-cloudlinux-licensing/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-cloudlinux-licensing/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-cloudlinux-licensing/version)](https://packagist.org/packages/detain/myadmin-cloudlinux-licensing)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-cloudlinux-licensing/downloads)](https://packagist.org/packages/detain/myadmin-cloudlinux-licensing)
[![License](https://poser.pugx.org/detain/myadmin-cloudlinux-licensing/license)](https://packagist.org/packages/detain/myadmin-cloudlinux-licensing)

A MyAdmin plugin for managing CloudLinux, KernelCare, and Imunify360 license provisioning. This package integrates with the MyAdmin panel plugin system using Symfony EventDispatcher to handle license activation, deactivation, IP changes, and listing through the CloudLinux XML-RPC API.

## Features

- Automated provisioning of CloudLinux, KernelCare, and Imunify360 licenses
- License activation and deactivation with IP address management
- IP address change support with automatic license migration
- Admin-only license listing interface
- Out-of-stock control via settings
- Email notifications for failed deactivations

## Supported License Types

| Product                       | Type ID |
|-------------------------------|---------|
| CloudLinux License            | 1       |
| KernelCare License            | 16      |
| ImunityAV+                    | 40      |
| Imunity360 Single User        | 41      |
| Imunity360 Up to 30 Users     | 42      |
| Imunity360 Up to 250 Users    | 43      |
| Imunity360 Unlimited Users    | 49      |

## Requirements

- PHP >= 5.3.0
- ext-curl
- `detain/cloudlinux-licensing` (CloudLinux API client)
- `symfony/event-dispatcher` ^5.0

## Installation

```sh
composer require detain/myadmin-cloudlinux-licensing
```

## Configuration

The plugin requires the following constants to be defined in your application:

```php
define('CLOUDLINUX_LOGIN', 'your-login');
define('CLOUDLINUX_KEY', 'your-api-key');
define('OUTOFSTOCK_LICENSES_CLOUDLINUX', 0); // 0 = in stock, 1 = out of stock
```

## Usage

Register the plugin hooks with your Symfony EventDispatcher instance:

```php
use Detain\MyAdminCloudlinux\Plugin;

$hooks = Plugin::getHooks();
foreach ($hooks as $event => $handler) {
    $dispatcher->addListener($event, $handler);
}
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

To generate a coverage report:

```sh
vendor/bin/phpunit --coverage-text
```

## License

This package is licensed under the [LGPL-2.1-only](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) license.
