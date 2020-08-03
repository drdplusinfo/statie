<?php declare(strict_types=1);

namespace Symplify\Statie\Tests\FileSystem;

use Nette\Utils\FileSystem;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;
use Symplify\PackageBuilder\Tests\AbstractKernelTestCase;
use Symplify\Statie\Configuration\StatieConfiguration;
use Symplify\Statie\FileSystem\FileSystemWriter;
use Symplify\Statie\HttpKernel\StatieKernel;
use Symplify\Statie\Renderable\File\FileFactory;

final class FileSystemWriterTest extends AbstractKernelTestCase
{
    /**
     * @var string
     */
    private $sourceDirectory = __DIR__ . '/FileSystemWriterSource/source';

    /**
     * @var string
     */
    private $outputDirectory = __DIR__ . '/FileSystemWriterSource/output';

    /**
     * @var FileSystemWriter
     */
    private $fileSystemWriter;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    protected function setUp(): void
    {
        $this->bootKernel(StatieKernel::class);

        $configuration = self::$container->get(StatieConfiguration::class);
        $configuration->setSourceDirectory($this->sourceDirectory);
        $configuration->setOutputDirectory($this->outputDirectory);

        $this->fileSystemWriter = self::$container->get(FileSystemWriter::class);

        $this->fileFactory = self::$container->get(FileFactory::class);
    }

    protected function tearDown(): void
    {
        FileSystem::delete($this->outputDirectory);
    }

    public function testCopyStaticFiles(): void
    {
        $file = new SmartFileInfo($this->sourceDirectory . '/index.html');
        $this->fileSystemWriter->copyStaticFiles([$file]);

        self::assertFileEquals($this->sourceDirectory . '/index.html', $this->outputDirectory . '/index.html');
    }

    public function testCopyRenderableFiles(): void
    {
        $fileInfo = new SmartFileInfo($this->sourceDirectory . '/contact.latte');
        $file = $this->fileFactory->createFromFileInfo($fileInfo);
        $file->setOutputPath('contact.html');

        $this->fileSystemWriter->renderFiles([$file]);

        self::assertFileEquals(
            $this->sourceDirectory . '/contact.latte',
            $this->outputDirectory . '/contact.html'
        );
    }

    public function testRenderFileWithAutoVersionedAssets(): void
    {
        $fileInfo = new SmartFileInfo($this->sourceDirectory . '/fileWithAssets.html');
        $file = $this->fileFactory->createFromFileInfo($fileInfo);
        $file->setOutputPath('fileWithAssets.html');

        $this->fileSystemWriter->renderFiles([$file]);

        self::assertFileEquals(
            $this->sourceDirectory . '/expectedFileWithAssets.html',
            $this->outputDirectory . '/fileWithAssets.html'
        );
    }

}
