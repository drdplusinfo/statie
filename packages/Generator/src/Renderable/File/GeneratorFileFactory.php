<?php declare(strict_types=1);

namespace Symplify\Statie\Generator\Renderable\File;

use Nette\Utils\Strings;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;
use Symplify\Statie\Configuration\StatieConfiguration;
use Symplify\Statie\Utils\PathAnalyzer;

final class GeneratorFileFactory
{
    /**
     * Matches "id: 2019-04-04"
     * @var string
     */
    private const ID_PATTERN = '#^id:[\s]*(?<id>\S+)#m';

    /**
     * @var PathAnalyzer
     */
    private $pathAnalyzer;

    /**
     * @var GeneratorFileGuard
     */
    private $generatorFileGuard;

    /**
     * @var StatieConfiguration
     */
    private $statieConfiguration;

    public function __construct(
        PathAnalyzer $pathAnalyzer,
        GeneratorFileGuard $generatorFileGuard,
        StatieConfiguration $statieConfiguration
    ) {
        $this->pathAnalyzer = $pathAnalyzer;
        $this->generatorFileGuard = $generatorFileGuard;
        $this->statieConfiguration = $statieConfiguration;
    }

    /**
     * @param SmartFileInfo[] $fileInfos
     * @return AbstractGeneratorFile[]
     */
    public function createFromFileInfosAndClass(array $fileInfos, string $class): array
    {
        $objects = [];

        $this->generatorFileGuard->ensureIsAbstractGeneratorFile($class);

        foreach ($fileInfos as $fileInfo) {
            $generatorFile = $this->createFromClassNameAndFileInfo($class, $fileInfo);
            $objects[$generatorFile->getId()] = $generatorFile;
        }

        return $objects;
    }

    private function createFromClassNameAndFileInfo(
        string $className,
        SmartFileInfo $smartFileInfo
    ): AbstractGeneratorFile {
        $id = $this->findAndGetValidId($smartFileInfo, $className);

        return new $className(
            $id,
            $smartFileInfo,
            $smartFileInfo->getRelativeFilePathFromDirectory($this->statieConfiguration->getSourceDirectory()),
            $smartFileInfo->getPathname(),
            $this->pathAnalyzer->detectFilenameWithoutDate($smartFileInfo),
            $this->pathAnalyzer->detectDate($smartFileInfo)
        );
    }

    private function findAndGetValidId(SmartFileInfo $smartFileInfo, string $className): string
    {
        $match = Strings::match($smartFileInfo->getContents(), self::ID_PATTERN);
        $this->generatorFileGuard->ensureIdIsSet($smartFileInfo, $match);

        $id = (string) $match['id'];
        $this->generatorFileGuard->ensureIdIsUnique($id, $className, $smartFileInfo);

        return $id;
    }
}
