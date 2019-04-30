<?php declare(strict_types=1);

namespace Symplify\Statie\Generator\Configuration;

use Nette\Utils\FileSystem;
use Symplify\Statie\Configuration\StatieConfiguration;
use Symplify\Statie\Generator\FileNameObjectSorter;
use Symplify\Statie\Generator\FilesComparator;
use Symplify\Statie\Generator\Renderable\File\GeneratorFile;

final class GeneratorElementFactory
{
    /**
     * @var GeneratorElementGuard
     */
    private $generatorElementGuard;

    /**
     * @var StatieConfiguration
     */
    private $statieConfiguration;
    /**
     * @var FilesComparator
     */
    private $filesComparator;

    public function __construct(GeneratorElementGuard $generatorElementGuard, StatieConfiguration $statieConfiguration, FilesComparator $filesComparator)
    {
        $this->generatorElementGuard = $generatorElementGuard;
        $this->statieConfiguration = $statieConfiguration;
        $this->filesComparator = $filesComparator;
    }

    /**
     * @param mixed[] $configuration
     */
    public function createFromConfiguration(string $name, array $configuration): GeneratorElement
    {
        $this->generatorElementGuard->ensureInputIsValid($name, $configuration);

        $configuration = $this->makePathAbsolute($configuration);

        return new GeneratorElement(
            $configuration['variable'],
            $configuration['variable_global'],
            $configuration['path'],
            $configuration['layout'],
            $configuration['route_prefix'],
            $configuration['object'] ?? GeneratorFile::class,
            isset($configuration['object_sorter']) ? new $configuration['object_sorter']() : new FileNameObjectSorter($this->filesComparator),
            // headline linker is on by default
            isset($configuration['has_headline_anchors']) ? (bool)$configuration['has_headline_anchors'] : true
        );
    }

    /**
     * @param mixed[] $configuration
     * @return mixed[]
     */
    private function makePathAbsolute(array $configuration): array
    {
        $this->ensureSourceDirectoryExists();

        $configuration['path'] = realpath($this->statieConfiguration->getSourceDirectory()) .
            DIRECTORY_SEPARATOR .
            $configuration['path'];

        return $configuration;
    }

    private function ensureSourceDirectoryExists(): void
    {
        if (file_exists($this->statieConfiguration->getSourceDirectory())) {
            return;
        }

        FileSystem::createDir($this->statieConfiguration->getSourceDirectory());
    }
}
