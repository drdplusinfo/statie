<?php declare(strict_types=1);

namespace Symplify\Statie\FileSystem;

use Granam\AssetsVersion\AssetsVersionInjector;
use Nette\Utils\FileSystem;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;
use Symplify\Statie\Configuration\StatieConfiguration;
use Symplify\Statie\Contract\File\RenderableFileInterface;

final class FileSystemWriter
{
    /**
     * @var StatieConfiguration
     */
    private $statieConfiguration;
    /**
     * @var AssetsVersionInjector
     */
    private $assetsVersionInjector;
    /**
     * @var string|null
     */
    private $assetsRootDir;
    /**
     * @var string
     */
    private $assetsAutoVersionExcludingRegexp;

    public function __construct(
        StatieConfiguration $statieConfiguration,
        AssetsVersionInjector $assetsVersionInjector
/*        string $assetsRootDir = null,
        string $assetsAutoVersionExcludingRegexp = AssetsVersionInjector::NO_REGEXP_TO_EXCLUDE_LINKS*/
    )
    {
        $this->statieConfiguration = $statieConfiguration;
        $this->assetsVersionInjector = $assetsVersionInjector;
/*        $this->assetsRootDir = $assetsRootDir;
        $this->assetsAutoVersionExcludingRegexp = $assetsAutoVersionExcludingRegexp;*/
    }

    /**
     * @param SmartFileInfo[] $files
     */
    public function copyStaticFiles(array $files): void
    {
        foreach ($files as $file) {
            $relativePathToSource = $file->getRelativeFilePathFromDirectory(
                $this->statieConfiguration->getSourceDirectory()
            );
            $absoluteDestination = $this->statieConfiguration->getOutputDirectory() . DIRECTORY_SEPARATOR . $relativePathToSource;

            FileSystem::copy($file->getRelativeFilePath(), $absoluteDestination, true);
        }
    }

    /**
     * @param RenderableFileInterface[] $files
     */
    public function renderFiles(array $files): void
    {
        foreach ($files as $file) {
            $absoluteDestination = $this->statieConfiguration->getOutputDirectory()
                . DIRECTORY_SEPARATOR
                . $file->getOutputPath();

            $content = $file->getContent();

            if (is_callable([$file, 'getFilePath'])) {
                $content = $this->assetsVersionInjector->addVersionsToAssetLinks(
                    $content,
                    $this->statieConfiguration->getOption('assets')['root_dir'] ?? dirname($file->getFilePath()),
                    $this->statieConfiguration->getOption('assets')['auto_version_excluding_regexp'] ?? '',
                    $file->getFilePath()
                );
            }

            FileSystem::write($absoluteDestination, $content);
        }
    }
}
