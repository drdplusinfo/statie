<?php declare(strict_types=1);

use Symfony\Component\DependencyInjection\Container;
use Symplify\Statie\Console\StatieConsoleApplication;

require_once __DIR__ . '/autoload.php';

// performance boost
gc_disable();

require_once __DIR__ . '/env.php';

/** @var Container $container */
$container = require __DIR__ . '/container.php';

return $container->get(StatieConsoleApplication::class);
