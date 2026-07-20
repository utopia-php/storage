# Utopia Storage

> [!IMPORTANT]
> This repository is a read-only mirror of the [utopia-php monorepo](https://github.com/utopia-php/monorepo). Development happens in [`packages/storage`](https://github.com/utopia-php/monorepo/tree/main/packages/storage) — please open issues and pull requests there.

![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/storage.svg)
[![Discord](https://img.shields.io/discord/564160730845151244?label=discord)](https://appwrite.io/discord)

Utopia Storage is a simple and lightweight library for managing application storage across multiple adapters. This library is designed to be easy to learn and use, with a consistent API regardless of the storage provider. This library is maintained by the [Appwrite team](https://appwrite.io).

This library is part of the [Utopia Framework](https://github.com/utopia-php/framework) project.

## Getting started

Install using Composer:
```bash
composer require utopia-php/storage
```

### Basic usage

Devices are immutable value objects: construct one with its configuration and use it anywhere, including across coroutines.

```php
<?php

require_once '../vendor/autoload.php';

use Utopia\Storage\Device\Local;

$device = new Local('/path/to/storage');

// Upload a file
$device->uploadData(file_get_contents('/local/path/to/file.png'), 'destination/path/file.png', 'image/png');

// Check if file exists
$exists = $device->exists('destination/path/file.png');

// Read file contents
$contents = $device->read('destination/path/file.png');

// Delete a file
$device->delete('destination/path/file.png');
```

## Available adapters

### Local storage

Use the local filesystem for storing files.

```php
use Utopia\Storage\Device\Local;

$device = new Local('/path/to/storage');
```

### AWS S3

Store files in Amazon S3 or compatible services.

```php
use Utopia\Storage\Acl;
use Utopia\Storage\Device\S3;

$device = new S3(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME.s3.us-east-1.amazonaws.com', // Host
    'us-east-1', // Region
    Acl::Private // Access control (default: private)
);
```

The provider-specific adapters below build the host for you from a bucket and region. Every S3-family adapter also accepts optional named constructor arguments:

```php
use Utopia\Storage\Acl;
use Utopia\Storage\Device\AWS;

$device = new AWS(
    'root',
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    AWS::US_EAST_1,
    Acl::Private,
    client: $psrClient, // any PSR-18 client (default: utopia-php/client with retries, see below)
);

// Available ACL options
// Acl::Private, Acl::PublicRead, Acl::PublicReadWrite, Acl::AuthenticatedRead
```

### DigitalOcean Spaces

Store files in DigitalOcean Spaces.

```php
use Utopia\Storage\Acl;
use Utopia\Storage\Device\DOSpaces;

$device = new DOSpaces(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    DOSpaces::NYC3, // Region (default: nyc3)
    Acl::Private // Access control (default: private)
);

// Available regions
// DOSpaces::NYC3, DOSpaces::SGP1, DOSpaces::FRA1, DOSpaces::SFO2, DOSpaces::SFO3, DOSpaces::AMS3
```

### Backblaze B2

Store files in Backblaze B2 Cloud Storage.

```php
use Utopia\Storage\Acl;
use Utopia\Storage\Device\Backblaze;

$device = new Backblaze(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    Backblaze::US_WEST_004, // Region (default: us-west-004)
    Acl::Private // Access control (default: private)
);

// Available regions (clusters)
// Backblaze::US_WEST_000, Backblaze::US_WEST_001, Backblaze::US_WEST_002,
// Backblaze::US_WEST_004, Backblaze::EU_CENTRAL_003
```

### Linode Object Storage

Store files in Linode Object Storage.

```php
use Utopia\Storage\Acl;
use Utopia\Storage\Device\Linode;

$device = new Linode(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    Linode::EU_CENTRAL_1, // Region (default: eu-central-1)
    Acl::Private // Access control (default: private)
);

// Available regions
// Linode::EU_CENTRAL_1, Linode::US_SOUTHEAST_1, Linode::US_EAST_1, Linode::AP_SOUTH_1
```

### Wasabi cloud storage

Store files in Wasabi Cloud Storage.

```php
use Utopia\Storage\Acl;
use Utopia\Storage\Device\Wasabi;

$device = new Wasabi(
    'root', // Root path in bucket
    'YOUR_ACCESS_KEY',
    'YOUR_SECRET_KEY',
    'YOUR_BUCKET_NAME',
    Wasabi::EU_CENTRAL_1, // Region (default: eu-central-1)
    Acl::Private // Access control (default: private)
);

// Available regions
// Wasabi::US_EAST_1, Wasabi::US_EAST_2, Wasabi::US_WEST_1, Wasabi::US_CENTRAL_1,
// Wasabi::EU_CENTRAL_1, Wasabi::EU_CENTRAL_2, Wasabi::EU_WEST_1, Wasabi::EU_WEST_2,
// Wasabi::AP_NORTHEAST_1, Wasabi::AP_NORTHEAST_2
```

## Common operations

All storage adapters provide a consistent API for working with files:

```php
// Upload file contents
$device->uploadData(file_get_contents('/path/to/local/file.jpg'), 'remote/path/file.jpg', 'image/jpeg');

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
$metadata = [];
$device->uploadData($firstChunk, 'remote/video.mp4', 'video/mp4', 1, 3, $metadata); // Part 1 of 3

// Resumable uploads: prepare, upload chunks in any order, finalize
$device->prepareUpload('remote/video.mp4', 'video/mp4', 3, $metadata);
$device->uploadChunk($secondChunk, 'remote/video.mp4', 2, 3, $metadata);
$device->finalizeUpload('remote/video.mp4', 3, $metadata);

// List files under a prefix, one page at a time
$list = $device->listFiles('remote/directory', 100);
foreach ($list->files as $file) {
    echo $file->path . ' (' . $file->size . " bytes)\n";
}
if ($list->cursor !== null) {
    $list = $device->listFiles('remote/directory', 100, $list->cursor); // Next page
}

// Delete file
$device->delete('remote/path/file.jpg');

// Delete directory
$device->deletePath('remote/directory');

// Transfer files between storage devices
$sourceDevice->transfer('source/path.jpg', 'target/path.jpg', $targetDevice);

// Transfer with a custom chunk size (default: 20 MB)
$sourceDevice->transfer('source/path.jpg', 'target/path.jpg', $targetDevice, 10000000);
```

## Custom HTTP client

The S3-family adapters send requests through any [PSR-18](https://www.php-fig.org/psr/psr-18/) client. By default they use [utopia-php/client](https://github.com/utopia-php/client) with the cURL adapter, no request timeout, and the `Retry` decorator configured with `S3\RetryStrategy` — it retries transient S3 rate-limiting errors (`SlowDown`, `ServiceUnavailable`, `Throttling`, `RequestThrottled`, and plain 429/503 responses) three times with a 500 ms delay.

Inject your own client to change the transport or the retry policy — for example the Swoole coroutine adapter with more aggressive retries:

```php
use Utopia\Client;
use Utopia\Client\Adapter\SwooleCoroutine\Client as SwooleAdapter;
use Utopia\Client\Decorator\Retry;
use Utopia\Storage\Device\S3;
use Utopia\Storage\Device\S3\RetryStrategy;

$client = new Retry(
    new Client(new SwooleAdapter())->withTimeout(60),
    new RetryStrategy(retries: 5, delay: 1.0),
);

$device = new S3('root', 'ACCESS_KEY', 'SECRET_KEY', 'HOST', 'us-east-1', client: $client);
```

Omit the `Retry` decorator to disable retries entirely, or pass any `Utopia\Client\Decorator\Retry\Strategy` of your own.

## Error handling

Every runtime failure throws a subclass of `Utopia\Storage\Exception\StorageException`, so one catch covers any storage problem. Match more precisely when you need to branch:

```php
use Utopia\Storage\Exception\NotFoundException;
use Utopia\Storage\Exception\RemoteException;
use Utopia\Storage\Exception\StorageException;
use Utopia\Storage\Exception\TransportException;
use Utopia\Storage\Exception\UploadException;

try {
    $contents = $device->read('remote/path/file.jpg');
} catch (NotFoundException) {
    // The file does not exist
} catch (TransportException $e) {
    // The request never reached the service; $e->getPrevious() is the PSR-18 exception
} catch (RemoteException $e) {
    // The service answered with an error: $e->getCode() is the HTTP status,
    // $e->errorCode the service's own error identifier (for example `SlowDown`)
} catch (UploadException) {
    // A chunked upload is in an invalid state (missing chunk, never prepared)
} catch (StorageException $e) {
    // Anything else, such as a local filesystem failure
}
```

Invalid arguments (a non-positive chunk size, a page size above the adapter limit) throw SPL `\InvalidArgumentException` — these are programmer errors, not storage failures.

## Telemetry

Wrap any device with the `Telemetry` decorator to record a `storage.operation` histogram for every call through a [utopia-php/telemetry](https://github.com/utopia-php/telemetry) adapter:

```php
use Utopia\Storage\Device\Local;
use Utopia\Storage\Device\Telemetry;

$device = new Telemetry($telemetryAdapter, new Local('/path/to/storage'));
```

## Upgrading from 2.x

Version 3.0 makes every device immutable and safe to share across coroutines, and removes all global state:

- The `Storage` class is gone entirely: replace `Storage::setDevice('files', $device)` and `Storage::getDevice('files')` with your own wiring (a container, or passing the device instance directly), and inline `Storage::human()` if you used it.
- `setTelemetry()` is gone: wrap the device in the `Utopia\Storage\Device\Telemetry` decorator instead. `setHttpVersion()` is gone: transport options now belong to the injected PSR-18 client. The static `S3::setRetryAttempts()`/`S3::setRetryDelay()` moved into the `S3\RetryStrategy` used by the default client's `Retry` decorator — inject your own client to tune or disable retries.
- `setTransferChunkSize()`/`getTransferChunkSize()` became a per-call argument: `transfer($path, $destination, $device, $chunkSize)`.
- String constants became enums: the `Storage::DEVICE_*` constants are now the `Utopia\Storage\DeviceType` enum (`getType()` returns it), and the `S3::ACL_*` constants are now the `Utopia\Storage\Acl` enum.
- The S3 adapter no longer stores request headers on the instance, so one device can serve concurrent requests (for example Swoole coroutines) without data races.
- Requests go through a PSR-18 client instead of raw cURL calls. The default is [utopia-php/client](https://github.com/utopia-php/client) with the cURL adapter; pass the `client` constructor argument to swap the transport.
- Uploads are data-based: `upload($sourcePath, ...)` was removed — read the file yourself and call `uploadData($data, ...)` — and `uploadChunk()` now takes the chunk contents instead of a source file path.
- Filesystem-only methods (`createDirectory()`, `getDirectorySize()`, `getPartitionFreeSpace()`, `getPartitionTotalSpace()`) left the base `Device` contract and remain on `Local`; wrap devices in the `Telemetry` decorator and use its `getDevice()` accessor when you need them. `getFiles()` — which returned path strings on `Local` but a raw `ListObjectsV2` array on `S3` — is replaced by `listFiles(prefix, max, cursor)` returning typed `FileList`/`FileInfo` value objects consistently on every adapter.
- `getName()` and `getDescription()` were removed; use `getType()` to identify an adapter.
- Exceptions are typed: every runtime failure extends `Utopia\Storage\Exception\StorageException` (`NotFoundException`, `TransportException`, `RemoteException`, `UploadException`) instead of bare `\Exception`, and missing files throw `NotFoundException` consistently on every adapter and method — including S3 HEAD responses, which previously surfaced as a generic error with an empty message. Existing `catch (\Exception)` blocks keep working.

## Adding new adapters

For information on adding new storage adapters, see the [Adding New Storage Adapter](https://github.com/utopia-php/storage/blob/master/docs/adding-new-storage-adapter.md) guide.

## System requirements

Utopia Storage requires PHP 8.5 or later. We recommend using the latest PHP version whenever possible.

## Contributing

For security issues, please email [security@appwrite.io](mailto:security@appwrite.io) instead of posting a public issue in GitHub.

All code contributions - including those of people having commit access - must go through a pull request and be approved by a core developer before being merged. This is to ensure a proper review of all the code.

We welcome you to contribute to the Utopia Storage library. For details on how to do this, please refer to our [Contributing Guide](https://github.com/utopia-php/monorepo/blob/main/CONTRIBUTING.md).

## License

This library is available under the MIT License.

## Copyright

```
Copyright (c) 2019-2025 Appwrite Team <team@appwrite.io>
```
