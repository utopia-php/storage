<?php
require_once '../vendor/autoload.php';

use Utopia\Storage\Device\Local;
use Utopia\Storage\Storage;

// instiantiating local storage
Storage::setDevice('files', new Local('files'));

$device = Storage::getDevice('files');

$source = __DIR__ . "/resources/disk-a/large_file.mp4";

$totalSize = filesize($source);

$chunkSize = 2097152;

$chunks = (int) ceil($totalSize / $chunkSize);

$chunk = 0;
$start = 0;

$handle = @fopen($source, "rb");
while ($start < $totalSize) {
    $contents = fread($handle, $chunkSize);
    $op = __DIR__ . '/chunk.part';
    $cc = fopen($op, 'wb');
    fwrite($cc, $contents);
    fclose($cc);
    $uploaded = $device->upload($op, $device->getPath('file.mp4'), $chunk, $chunks, $device->getPath('tmp/chunks.log'));
    $start += strlen($contents);
    $chunk++;
    fseek($handle, $start);
}
unlink(__DIR__ . '/chunk.part');
@fclose($handle);
