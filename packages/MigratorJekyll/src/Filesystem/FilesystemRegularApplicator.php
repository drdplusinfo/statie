<?php declare(strict_types=1);

namespace Symplify\Statie\MigratorJekyll\Filesystem;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;
use function Safe\getcwd;
use function Safe\sprintf;

final class FilesystemRegularApplicator
{
    /**
     * @var MigratorFilesystem
     */
    private $migratorFilesystem;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(MigratorFilesystem $migratorFilesystem, SymfonyStyle $symfonyStyle)
    {
        $this->migratorFilesystem = $migratorFilesystem;
        $this->symfonyStyle = $symfonyStyle;
    }

    /**
     * @param string[][] $pathsToRegulars
     */
    public function processPaths(array $pathsToRegulars): void
    {
        foreach ($pathsToRegulars as $path => $regulars) {
            // null → this directory
            $path = $this->migratorFilesystem->absolutizePath($path);
            if (! file_exists($path) || ! is_dir($path)) {
                continue;
            }

            $fileInfos = $this->migratorFilesystem->findFiles($path);

            foreach ($regulars as $regularPattern => $replacePattern) {
                $this->processFoundFiles($fileInfos, $regularPattern, $replacePattern);
            }
        }
    }

    /**
     * @param SmartFileInfo[] $fileInfos
     */
    private function processFoundFiles(array $fileInfos, string $regularPattern, string $replacePattern): void
    {
        foreach ($fileInfos as $fileInfo) {
            $oldContent = $fileInfo->getContents();
            $newContent = Strings::replace($oldContent, $regularPattern, $replacePattern);
            if ($newContent === $oldContent) {
                continue;
            }

            FileSystem::write($fileInfo->getRealPath(), $newContent);

            $this->symfonyStyle->note(sprintf(
                'File "%s" was cleared by %s regular',
                $fileInfo->getRelativeFilePathFromDirectory(getcwd()),
                $regularPattern
            ));
        }
    }
}
