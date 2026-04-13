<?php

foreach ([
    __DIR__.'/../bootstrap/cache/config.php',
] as $cacheFile) {
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
}

$env = [
    'APP_ENV'                => 'testing',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS'          => '4',
    'CACHE_STORE'            => 'array',
    'DB_CONNECTION'          => 'sqlite',
    'DB_DATABASE'            => ':memory:',
    'MAIL_MAILER'            => 'array',
    'QUEUE_CONNECTION'       => 'sync',
    'SESSION_DRIVER'         => 'array',
];

foreach ($env as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require __DIR__.'/../vendor/autoload.php';
