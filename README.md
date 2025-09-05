# Utopia Storage

[![Build Status](https://travis-ci.org/utopia-php/storage.svg?branch=master)](https://travis-ci.com/utopia-php/storage)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/storage.svg)
[![Discord](https://img.shields.io/discord/564160730845151244?label=discord)](https://appwrite.io/discord)

Utopia Storage is a simple and lightweight library for managing application storage across multiple adapters. This library is designed to be easy to learn and use, with a consistent API regardless of the storage provider. This library is maintained by the [Appwrite team](https://appwrite.io).

This library is part of the [Utopia Framework](https://github.com/utopia-php/framework) project.

## Getting Started

Install using composer:
```bash
composer require utopia-php/storage
```

### Basic Usage

```php
<?php

require_once '../vendor/autoload.php';

use Utopia\Storage\Storage;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Device\S3;
use Utopia\Storage\Device\DOSpaces;
use Utopia\Storage\Device\Backblaze;
use Utopia\Storage\Device\Linode;
use Utopia\Storage\Device\Wasabi;

// Set up a storage device (only need to choose one)
Storage::setDevice('files', new Local('/path/to/storage'));

// Common operations with any device
$device = Storage::getDevice('files');

// Upload a file
$device->upload('/local/path/to/file.png', 'destination/path/file.png');

// Check if file exists
$exists = $device->exists('destination/path/file.png');

// Read file contents
$contents = $device->read('destination/path/file.png');

// Delete a file
$device->delete('destination/path/file.png');
```

## Available Adapters

### Local Storage

Use the local filesystem for storing files.

```php
use Utopia\Storage\Storage;
use Utopia\Storage\Device\Local;

// Initialize local storage
Storage::setDevice('files', new Local('/path/to/storage'));
```

### AWS S3

Store files in Amazon S3 or compatible services.

```php
use Utopia\Storage\Storage;
use Utopia\Storage\Device\S3;

// Initialize S3 storage
Storage::setDevice('files', new S3(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    S3::US_EAST_1, // Region (default: us-east-1)
    S3::ACL_PRIVATE // Access control (default: private)
));

// Available regions
// S3::US_EAST_1, S3::US_EAST_2, S3::US_WEST_1, S3::US_WEST_2, S3::AP_SOUTH_1, 
// S3::AP_NORTHEAST_1, S3::AP_NORTHEAST_2, S3::AP_NORTHEAST_3, S3::AP_SOUTHEAST_1,
// S3::AP_SOUTHEAST_2, S3::EU_CENTRAL_1, S3::EU_WEST_1, S3::EU_WEST_2, S3::EU_WEST_3,
// And more - check the S3 class for all available regions

// Available ACL options
// S3::ACL_PRIVATE, S3::ACL_PUBLIC_READ, S3::ACL_PUBLIC_READ_WRITE, S3::ACL_AUTHENTICATED_READ
```

### DigitalOcean Spaces

Store files in DigitalOcean Spaces.

```php
use Utopia\Storage\Storage;
use Utopia\Storage\Device\DOSpaces;

// Initialize DO Spaces storage
Storage::setDevice('files', new DOSpaces(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    DOSpaces::NYC3, // Region (default: nyc3)
    DOSpaces::ACL_PRIVATE // Access control (default: private)
));

// Available regions
// DOSpaces::NYC3, DOSpaces::SGP1, DOSpaces::FRA1, DOSpaces::SFO2, DOSpaces::SFO3, DOSpaces::AMS3
```

### Backblaze B2

Store files in Backblaze B2 Cloud Storage.

```php
use Utopia\Storage\Storage;
use Utopia\Storage\Device\Backblaze;

// Initialize Backblaze storage
Storage::setDevice('files', new Backblaze(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    Backblaze::US_WEST_004, // Region (default: us-west-004)
    Backblaze::ACL_PRIVATE // Access control (default: private)
));

// Available regions (clusters)
// Backblaze::US_WEST_000, Backblaze::US_WEST_001, Backblaze::US_WEST_002, 
// Backblaze::US_WEST_004, Backblaze::EU_CENTRAL_003
```

### Linode Object Storage

Store files in Linode Object Storage.

```php
use Utopia\Storage\Storage;
use Utopia\Storage\Device\Linode;

// Initialize Linode storage
Storage::setDevice('files', new Linode(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    Linode::EU_CENTRAL_1, // Region (default: eu-central-1)
    Linode::ACL_PRIVATE // Access control (default: private)
));

// Available regions
// Linode::EU_CENTRAL_1, Linode::US_SOUTHEAST_1, Linode::US_EAST_1, Linode::AP_SOUTH_1
```

### Wasabi Cloud Storage

Store files in Wasabi Cloud Storage.

```php
use Utopia\Storage\Storage;
use Utopia\Storage\Device\Wasabi;

// Initialize Wasabi storage
Storage::setDevice('files', new Wasabi(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    Wasabi::EU_CENTRAL_1, // Region (default: eu-central-1)
    Wasabi::ACL_PRIVATE // Access control (default: private)
));

// Available regions
// Wasabi::US_EAST_1, Wasabi::US_EAST_2, Wasabi::US_WEST_1, Wasabi::US_CENTRAL_1,
// Wasabi::EU_CENTRAL_1, Wasabi::EU_CENTRAL_2, Wasabi::EU_WEST_1, Wasabi::EU_WEST_2,
// Wasabi::AP_NORTHEAST_1, Wasabi::AP_NORTHEAST_2
```

## Common Operations

All storage adapters provide a consistent API for working with files:

```php
// Get storage device
$device = Storage::getDevice('files');

// Upload a file
$device->upload('/path/to/local/file.jpg', 'remote/path/file.jpg');

// Check if file exists
$exists = $device->exists('remote/path/file.jpg');

// Get file size
$size = $device->getFileSize('remote/path/file.jpg');

// Get file MIME type
$mime = $device->getFileMimeType('remote/path/file.jpg');

// Get file MD5 hash
$hash = $device->getFileHash('remote/path/file.jpg');

// Read file contents
$contents = $device->read('remote/path/file.jpg');

// Read partial file contents
$chunk = $device->read('remote/path/file.jpg', 0, 1024); // Read first 1KB

// Multipart/chunked uploads
$device->upload('/local/file.mp4', 'remote/video.mp4', 1, 3); // Part 1 of 3

// Create directory
$device->createDirectory('remote/new-directory');

// List files in directory
$files = $device->listFiles('remote/directory');

// Delete file
$device->delete('remote/path/file.jpg');

// Delete directory
$device->deleteDirectory('remote/directory');

// Transfer files between storage devices
$sourceDevice = Storage::getDevice('source');
$targetDevice = Storage::getDevice('target');

$sourceDevice->transfer('source/path.jpg', 'target/path.jpg', $targetDevice);
```

## Adding New Adapters

For information on adding new storage adapters, see the [Adding New Storage Adapter](https://github.com/utopia-php/storage/blob/master/docs/adding-new-storage-adapter.md) guide.

## System Requirements

Utopia Storage requires PHP 7.4 or later. We recommend using the latest PHP version whenever possible.

## Contributing

For security issues, please email [security@appwrite.io](mailto:security@appwrite.io) instead of posting a public issue in GitHub.

All code contributions - including those of people having commit access - must go through a pull request and be approved by a core developer before being merged. This is to ensure a proper review of all the code.

We welcome you to contribute to the Utopia Storage library. For details on how to do this, please refer to our [Contributing Guide](https://github.com/utopia-php/storage/blob/master/CONTRIBUTING.md).

## License

This library is available under the MIT License.

## Copyright

```
Copyright (c) 2019-2025 Appwrite Team <team@appwrite.io>
```
