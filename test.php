<?php

require './vendor/autoload.php';

use Utopia\Storage\Device\Backblaze;

$secret = 'K004xt0+8gVUQwKKKxPjzQQJp9Sw6e0';
$key = '004a5e83cff2d8a000000000a';

$device = new Backblaze('/hello', $key, $secret, 'utopia-storage-test-new', Backblaze::US_WEST_004);

// $uploaded = $device->upload('./Bruce Wayne.png', $device->getPath('resting/test111.png'));
// var_dump($uploaded);

$device->upload(__DIR__.'/tests/resources/disk-a/kitten-1.jpg', $device->getPath('testing/kitten-1.jpg'));
var_dump('uploaded');
$device->upload(__DIR__.'/tests/resources/disk-a/kitten-2.jpg', $device->getPath('testing/kitten-2.jpg'));
var_dump('uploaded');
$device->upload(__DIR__.'/tests/resources/disk-b/kitten-1.png', $device->getPath('testing/kitten-1.png'));
var_dump('uploaded');
$device->upload(__DIR__.'/tests/resources/disk-b/kitten-2.png', $device->getPath('testing/kitten-2.png'));
var_dump('uploaded');

$path = $device->getPath('testing/');
$files = $device->getFiles($path);
var_dump($files);