<?php
use Cake\Cache\Cache;

/**
 * Test runner bootstrap.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

define('ROOT', dirname(__DIR__));
define('TMP', ROOT . DS . 'tmp' . DS);
if (!is_dir(TMP)) {
    mkdir(TMP, 0770, true);
}
define('CACHE', TMP . 'cache' . DS);
// Setup cache
Cache::setConfig([
    '_error_email_' => [
    'className' => 'File',
        'prefix' => 'error_email_',
        'path' => CACHE . 'error_emails/',
        'duration' => '+5 minutes'
    ]
]);
