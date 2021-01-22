# Utopia Storage

[![Build Status](https://travis-ci.org/utopia-php/ab.svg?branch=master)](https://travis-ci.com/utopia-php/storage)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/storage.svg)
[![Discord](https://img.shields.io/discord/564160730845151244?label=discord)](https://appwrite.io/discord)

Utopia Storage library is simple and lite library for managing application storage. It supports multiple storage adapters. Soon we will be adding more adapters. This library is aiming to be as simple and easy to learn and use. This library is maintained by the [Appwrite team](https://appwrite.io).

This library is part of the [Utopia Framework](https://github.com/utopia-php/framework) project.


## Getting Started

Install using composer:
```bash
composer require utopia-php/storage
```

```php
<?php

require_once '../vendor/autoload.php';

use Utopia\Storage\Storage;

Storage::setDevice('files', new Local('path'));

$device = Storage::getDevice('files');

//upload
$device->upload('file.png','path');

//delete
$device->delete('path');

```

## System Requirements

Utopia Framework requires PHP 7.4 or later. We recommend using the latest PHP version whenever possible.

## Authors

**Damodar Lohani**

+ [https://twitter.com/lohanidamodar](https://twitter.com/lohanidamodar)
+ [https://github.com/lohanidamodar](https://github.com/lohanidamodar)

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)