<?php declare(strict_types=1);

namespace Symplify\Statie\Generator;

use Symplify\Statie\Generator\Contract\ObjectSorterInterface;
use Symplify\Statie\Renderable\File\AbstractFile;

final class FileNameObjectSorter implements ObjectSorterInterface
{
    /**
     * @var FilesComparator
     */
    private $filesComparator;

    public function __construct(FilesComparator $filesComparator)
    {
        $this->filesComparator = $filesComparator;
    }

    /**
     * @param AbstractFile[] $generatorFiles
     * @return AbstractFile[]
     */
    public function sort(array $generatorFiles): array
    {
        uksort($generatorFiles, function ($firstFileIndex, $secondFileIndex) use ($generatorFiles): int {
            $firstFile = $generatorFiles[$firstFileIndex];
            $secondFile = $generatorFiles[$secondFileIndex];
            return $this->filesComparator->compare($secondFile, $firstFile, $secondFileIndex, $firstFileIndex);
        });
        return $generatorFiles;
    }
}
