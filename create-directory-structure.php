<?php 

$directories = [
    '/tmp/email-platform/writable/cache',
    '/tmp/email-platform/writable/logs',
    '/tmp/email-platform/writable/session',
    '/tmp/email-platform/writable/debugbar',
    '/tmp/email-platform/writable/uploads',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: {$dir}\n";
    } else {
        echo "Directory already exists: {$dir}\n";
    }
}