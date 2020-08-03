<?php declare(strict_types=1);

namespace Symplify\Statie\Generator\Tests;

use DateTimeInterface;
use Symplify\Statie\Configuration\StatieConfiguration;
use Symplify\Statie\Generator\Renderable\File\AbstractGeneratorFile;
use Symplify\Statie\HttpKernel\StatieKernel;

/**
 * @covers \Symplify\Statie\Generator\Generator
 */
final class GeneratorTest extends AbstractGeneratorTest
{
    protected function setUp(): void
    {
        $this->bootKernelWithConfigs(StatieKernel::class, [__DIR__ . '/GeneratorSource/statie.yml']);

        parent::setUp();
    }

    public function testIdsAreKeys(): void
    {
        $generatorFilesByType = $this->generator->run();

        foreach ($generatorFilesByType as $generatorFiles) {
            foreach ($generatorFiles as $key => $generatorFile) {
                /** @var AbstractGeneratorFile $generatorFile */
                self::assertSame((string)$key, $generatorFile->getId());
            }
        }
    }

    public function testIdsAreSorted(): void
    {
        $generatorFilesByType = $this->generator->run();
        $expectedKeys = [
            '2017-02-05-1',
            '2017-01-05-2',
            '2017-01-05-1',
            '2017-01-01-1',
            '2016-10-10-1',
            '2016-01-02-1',
        ];
        self::assertSame($expectedKeys, array_keys($generatorFilesByType['posts']));

        /** @var StatieConfiguration $statieConfiguration */
        $statieConfiguration = self::$container->get(StatieConfiguration::class);
        self::assertSame($expectedKeys, array_keys($statieConfiguration->getOption('posts')));
    }

    public function testPosts(): void
    {
        $generatorFilesByType = $this->generator->run();
        $postFiles = $generatorFilesByType['posts'];

        self::assertCount(6, $postFiles);

        $this->fileSystemWriter->renderFiles($postFiles);

        // posts
        self::assertFileExists($this->outputDirectory . '/blog/2016/10/10/title/index.html');
        self::assertFileExists($this->outputDirectory . '/blog/2016/01/02/second-title/index.html');

        self::assertFileExists($this->outputDirectory . '/blog/2017/01/01/some-post/index.html');
        self::assertFileExists($this->outputDirectory . '/blog/2017/01/05/another-related-post/index.html');
        self::assertFileExists($this->outputDirectory . '/blog/2017/01/05/some-related-post/index.html');

        self::assertFileExists($this->outputDirectory . '/blog/2017/02/05/offtopic-post/index.html');
    }

    public function testLatteBlocks(): void
    {
        $generatorFilesByType = $this->generator->run();
        $postFiles = $generatorFilesByType['posts'];

        $this->fileSystemWriter->renderFiles($postFiles);

        self::assertFileEquals(
            __DIR__ . '/GeneratorSource/expected/post-with-latte-blocks-expected.html',
            $this->outputDirectory . '/blog/2016/01/02/second-title/index.html'
        );
    }

    public function testLectures(): void
    {
        $generatorFilesByType = $this->generator->run();
        $lectureFiles = $generatorFilesByType['lectures'];

        self::assertCount(1, $lectureFiles);

        $this->fileSystemWriter->renderFiles($lectureFiles);

        // lectures
        self::assertFileExists($this->outputDirectory . '/lecture/open-source-lecture/index.html');
    }

    public function testConfiguration(): void
    {
        self::assertArrayNotHasKey('posts', $this->statieConfiguration->getOptions());
        self::assertArrayNotHasKey('lectures', $this->statieConfiguration->getOptions());

        $this->generator->run();

        self::assertArrayHasKey('posts', $this->statieConfiguration->getOptions());
        self::assertArrayHasKey('lectures', $this->statieConfiguration->getOptions());

        $posts = $this->statieConfiguration->getOption('posts');
        self::assertCount(6, $posts);

        $lectures = $this->statieConfiguration->getOption('lectures');
        self::assertCount(1, $lectures);

        // detect date correctly from name
        $firstPost = array_pop($posts);
        self::assertInstanceOf(DateTimeInterface::class, $firstPost['date']);
    }
}
