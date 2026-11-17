<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../server/router.php';

use fmihel\router\router;

try {
    $m = router::module([__DIR__ . '/../examples/03_routing']);
    echo "OK: $m\n";
} catch (\Throwable $e) {
    echo 'ERR ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}
