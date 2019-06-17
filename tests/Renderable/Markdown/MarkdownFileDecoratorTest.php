<?php declare(strict_types=1);

namespace Symplify\Statie\Tests\Renderable\Markdown;

use Iterator;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;
use Symplify\PackageBuilder\Tests\AbstractKernelTestCase;
use Symplify\Statie\Configuration\StatieConfiguration;
use Symplify\Statie\HttpKernel\StatieKernel;
use Symplify\Statie\Renderable\File\FileFactory;
use Symplify\Statie\Renderable\MarkdownFileDecorator;

final class MarkdownFileDecoratorTest extends AbstractKernelTestCase
{
    /**
     * @var MarkdownFileDecorator
     */
    private $markdownFileDecorator;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    protected function setUp(): void
    {
        $this->bootKernel(StatieKernel::class);

        $this->markdownFileDecorator = self::$container->get(MarkdownFileDecorator::class);
        $this->fileFactory = self::$container->get(FileFactory::class);

        $configuration = self::$container->get(StatieConfiguration::class);
        $configuration->setSourceDirectory(__DIR__ . '/MarkdownFileDecoratorSource');
    }

    /**
     * @dataProvider provideFilesToHtml()
     */
    public function testNotMarkdown(string $file, string $expectedContent, string $message): void
    {
        $fileInfo = new SmartFileInfo($file);
        $file = $this->fileFactory->createFromFileInfo($fileInfo);

        $this->markdownFileDecorator->decorateFiles([$file]);

        $this->assertStringContainsString($expectedContent, $file->getContent(), $message);
    }

    public function provideFilesToHtml(): Iterator
    {
        yield [
            __DIR__ . '/MarkdownFileDecoratorSource/someFile.latte',
            '# Content...',
            'No conversion with ".latte" suffix',
        ];
        yield [
            __DIR__ . '/MarkdownFileDecoratorSource/someFile.md',
            '<h1>Content...</h1>',
            'Conversion thanks to ".md" suffix',
        ];
    }

    public function testMarkdownTitle(): void
    {
        $fileInfo = new SmartFileInfo(__DIR__ . '/MarkdownFileDecoratorSource/someFile.md');
        $file = $this->fileFactory->createFromFileInfo($fileInfo);

        $file->addConfiguration([
            'title' => '*Why*',
        ]);

        $this->markdownFileDecorator->decorateFiles([$file]);

        $this->assertSame('<em>Why</em>', $file->getConfiguration()['title']);
    }

    public function testMarkdownPerex(): void
    {
        $fileInfo = new SmartFileInfo(__DIR__ . '/MarkdownFileDecoratorSource/someFile.md');
        $file = $this->fileFactory->createFromFileInfo($fileInfo);

        $file->addConfiguration([
            'perex' => '**Hey**',
        ]);

        $this->markdownFileDecorator->decorateFiles([$file]);

        $this->assertSame('<strong>Hey</strong>', $file->getConfiguration()['perex']);

        $file->addConfiguration([
            'image' => 'foo.png',
            'image_title' => 'Baz',
            'title' => 'Bar',
        ]);
        $this->markdownFileDecorator->decorateFiles([$file]);
        $this->assertSame('<img src="foo.png" alt="Bar" title="Baz"><strong>Hey</strong>', $file->getConfiguration()['perex']);
    }

    /**
     * @dataProvider provideLinkToLocalFile
     * @param string $filePath
     * @param string $expectedOutput
     */
    public function testLocalLinksToMarkdownFilesAreTurnedToValidRoute(string $filePath, string $expectedOutput): void
    {
        $fileInfo = new SmartFileInfo($filePath);
        $file = $this->fileFactory->createFromFileInfo($fileInfo);

        $this->markdownFileDecorator->decorateFiles([$file]);

        $this->assertSame($expectedOutput, $file->getContent());
    }

    public function provideLinkToLocalFile(): array
    {
        return [
            'file in same dir' => [
                __DIR__ . '/MarkdownFileDecoratorSource/2019/2019-01-02-fileWithLinkToMarkdownFileInCurrentDir.md',
                '<p><a href="../../../../2019/01/03/fileWithLinkToMarkdownFileInDifferentDir/">foo</a></p>',
            ],
            'file in another dir' => [
                __DIR__ . '/MarkdownFileDecoratorSource/2019/2019-01-03-fileWithLinkToMarkdownFileInDifferentDir.md',
                '<p><a href="../../../../2018/01/01/bar/#cas-klidu">foo</a></p>',
            ],
            'file in local hash' => [
                __DIR__ . '/MarkdownFileDecoratorSource/2019/2019-05-05-fileWithLocalHash.md',
                '<p><a href="#na-zdarbuh">local hash</a></p>',
            ],
        ];
    }
}
