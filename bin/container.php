<?php declare(strict_types=1);

use Symfony\Component\Console\Input\ArgvInput;
use Symplify\PackageBuilder\Configuration\ConfigFileFinder;
use Symplify\PackageBuilder\Console\Input\InputDetector;
use Symplify\Statie\HttpKernel\StatieKernel;
use Symfony\Component\Dotenv\Dotenv;

// Detect configuration from input
ConfigFileFinder::detectFromInput('statie', new ArgvInput());

// Fallback to file in root
$configFile = ConfigFileFinder::provide('statie', ['statie.yml', 'statie.yaml']);

// order is important, env variables are NOT overwritten on load
$envFiles = [ConfigFileFinder::provide('statie', ['.env.local']), ConfigFileFinder::provide('statie', ['.env'])];
$envFiles = array_filter($envFiles, function ($envFile) {
    return $envFile !== null;
});
if ($envFiles) {
    $dotenv = new Dotenv();
    foreach ($envFiles as $envFile) {
        $dotenv->load($envFile);
    }
}

// random has is needed, so cache is invalidated and changes from config are loaded
$environment = 'prod' . random_int(1, 100000);
$statieKernel = new StatieKernel($environment, InputDetector::isDebug());
if ($configFile !== null) {
    $statieKernel->setConfigs([$configFile]);
}
$statieKernel->boot();

return $statieKernel->getContainer();
