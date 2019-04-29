<?php
use Symfony\Component\Dotenv\Dotenv;
use Symplify\PackageBuilder\Configuration\ConfigFileFinder;

// order of files is important, env variables are NOT overwritten on load
$envFiles = [ConfigFileFinder::provide('.env.local', ['.env.local']), ConfigFileFinder::provide('.env', ['.env'])];
$envFiles = array_filter($envFiles, function ($envFile) {
    return $envFile !== null;
});
if ($envFiles) {
    $dotenv = new Dotenv();
    foreach ($envFiles as $envFile) {
        $dotenv->load($envFile);
    }
}