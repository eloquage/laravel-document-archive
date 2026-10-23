<?php

it('publishes the configured default collection disk', function () {
    $packageConfig = require __DIR__.'/../../config/document-archive.php';
    $diskConfig = require __DIR__.'/../../config/document-archive-disks.php';

    expect($diskConfig['disks'])->toHaveKey($packageConfig['disks']['collections']);
});
