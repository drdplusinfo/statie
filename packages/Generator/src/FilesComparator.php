<?php
namespace Symplify\Statie\Generator;

use Symplify\Statie\Renderable\File\AbstractFile;

class FilesComparator
{
    public function compare(AbstractFile $someFile, AbstractFile $anotherFile, $someValue = null, $anotherValue = null): int
    {
        $dateComparison = $someFile->getDate() <=> $anotherFile->getDate();
        if ($dateComparison !== 0) {
            return $dateComparison;
        }
        return strcmp((string)$someValue, (string)$anotherValue);
    }
}