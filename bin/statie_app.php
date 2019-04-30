<?php declare(strict_types=1);

use Composer\XdebugHandler\XdebugHandler;
use Symfony\Component\DependencyInjection\Container;
use Symplify\Statie\Console\StatieConsoleApplication;

require_once __DIR__ . '/autoload.php';

// performance boost
gc_disable();

require_once __DIR__ . '/env.php';

$xdebug = new XdebugHandler('statie', '--ansi');
$xdebug->check();
unset($xdebug);

/** @var Container $container */
$container = require __DIR__ . '/container.php';

return $container->get(StatieConsoleApplication::class);
