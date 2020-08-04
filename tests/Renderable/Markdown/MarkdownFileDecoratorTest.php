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
        $this->bootKernelWithConfigs(StatieKernel::class, [__DIR__ . '/config.yml']);

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

        self::assertStringContainsString($expectedContent, $file->getContent(), $message);
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

        self::assertSame('<em>Why</em>', $file->getConfiguration()['title']);
    }

    public function testMarkdownPerex(): void
    {
        $fileInfo = new SmartFileInfo(__DIR__ . '/MarkdownFileDecoratorSource/someFile.md');
        $file = $this->fileFactory->createFromFileInfo($fileInfo);

        $file->addConfiguration([
            'perex' => '**Hey**',
        ]);

        $this->markdownFileDecorator->decorateFiles([$file]);

        self::assertSame(<<<HTML
<span class="row perex-parts-uneven">
    <span class="col-lg-4 perex-image-container align-self-center"></span>
    <span class="col-lg-8 perex-text-container"><strong>Hey</strong></span>
</span>
HTML
            ,
            $file->getConfiguration()['perex']
        );

        $file->addConfiguration([
            'perex' => '**Hey**',
            'image' => '/assets/images/same_width_and_height.png',
            'image_title' => 'Baz',
            'title' => 'Bar',
        ]);
        $this->markdownFileDecorator->decorateFiles([$file]);
        self::assertSame(
            <<<HTML
<span class="row perex-parts-uneven">
    <span class="col-md-4 perex-image-container align-self-center"><img src="/assets/images/same_width_and_height.png" alt="Bar" title="Baz" width="460" height="460"></span>
    <span class="col-md-8 perex-text-container"><strong>Hey</strong></span>
</span>
HTML
            ,
            $file->getConfiguration()['perex']
        );

        $file->addConfiguration([
            'perex' => '**Bye**',
            'image' => '/assets/images/wide.png',
            'image_title' => 'Baz',
            'title' => 'Bar',
        ]);
        $this->markdownFileDecorator->decorateFiles([$file]);
        self::assertSame(
            <<<HTML
<span class="row perex-parts-even">
    <span class="col-lg perex-image-container align-self-center"><img src="/assets/images/wide.png" alt="Bar" title="Baz" width="600" height="426"></span>
    <span class="col-lg perex-text-container"><strong>Bye</strong></span>
</span>
HTML
            ,
            $file->getConfiguration()['perex']
        );
    }

    public function testMarkdownImageAuthor(): void
    {
        $fileInfo = new SmartFileInfo(__DIR__ . '/MarkdownFileDecoratorSource/someFile.md');
        $file = $this->fileFactory->createFromFileInfo($fileInfo);

        $file->addConfiguration([
            'image_author' => '**Foo** *Bar* [baz](qux)',
        ]);

        $this->markdownFileDecorator->decorateFiles([$file]);

        self::assertSame('<strong>Foo</strong> <em>Bar</em> <a href="qux">baz</a>', $file->getConfiguration()['image_author']);
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

        self::assertSame($expectedOutput, $file->getContent());
    }

    public function provideLinkToLocalFile(): array
    {
        return [
            'file in same dir' => [
                __DIR__ . '/MarkdownFileDecoratorSource/2019/2019-01-02-fileWithLinkToMarkdownFileInCurrentDir.md',
                '<p><a href="/blog/2019/01/03/fileWithLinkToMarkdownFileInDifferentDir/">foo</a></p>',
            ],
            'file in another dir' => [
                __DIR__ . '/MarkdownFileDecoratorSource/2019/2019-01-03-fileWithLinkToMarkdownFileInDifferentDir.md',
                '<p><a href="/blog/2018/01/01/bar/#cas-klidu">foo</a></p>',
            ],
            'file in local hash' => [
                __DIR__ . '/MarkdownFileDecoratorSource/2019/2019-05-05-fileWithLocalHash.md',
                '<p><a href="#na-zdarbuh">local hash</a></p>',
            ],
        ];
    }
}
