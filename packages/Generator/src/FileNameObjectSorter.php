<?php declare(strict_types=1);

namespace Symplify\Statie\Generator;

use Symplify\Statie\Generator\Contract\ObjectSorterInterface;
use Symplify\Statie\Renderable\File\AbstractFile;

final class FileNameObjectSorter implements ObjectSorterInterface
{
    /**
     * @param AbstractFile[] $generatorFiles
     * @return AbstractFile[]
     */
    public function sort(array $generatorFiles): array
    {
        uksort($generatorFiles, function ($firstFileIndex, $secondFileIndex) use ($generatorFiles): int {
            $firstFile = $generatorFiles[$firstFileIndex];
            $secondFile = $generatorFiles[$secondFileIndex];
            $firstFileDate = $firstFile->getDate();
            $secondFileDate = $secondFile->getDate();
            if ($firstFileDate && $secondFileDate) {
                $datesComparison = $secondFileDate <=> $firstFileDate;
                if ($datesComparison !== 0) {
                    return $datesComparison;
                }
            }
            return strcmp((string)$secondFileIndex, (string)$firstFileIndex);
        });

        return $generatorFiles;
    }
}
