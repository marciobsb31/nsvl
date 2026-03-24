<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "client_id:     " . config('govbr.client_id') . PHP_EOL;
echo "redirect_uri:  " . config('govbr.redirect_uri') . PHP_EOL;
echo "authorize_url: " . config('govbr.authorize_url') . PHP_EOL;
echo "sso_url:       " . config('govbr.sso_url') . PHP_EOL;
echo "scopes:        " . implode(' ', config('govbr.scopes', [])) . PHP_EOL;
