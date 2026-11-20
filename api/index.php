<?php

declare(strict_types=1);

// Vercel entry point: forward every request to Laravel's front controller.
// Vercel runs this file as /api/index.php, which makes Laravel treat /api as the
// base path and turn /api/face/sessions into /face/sessions. Present the request
// as if public/index.php served it from the web root.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
