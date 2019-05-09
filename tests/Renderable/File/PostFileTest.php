<?php declare(strict_types=1);

namespace Symplify\Statie\Tests\Renderable\File;

use Symplify\PackageBuilder\FileSystem\SmartFileInfo;
use Symplify\PackageBuilder\Tests\AbstractKernelTestCase;
use Symplify\Statie\HttpKernel\StatieKernel;
use Symplify\Statie\Renderable\File\PostFile;
use Symplify\Statie\Utils\PathAnalyzer;

class PostFileTest extends AbstractKernelTestCase
{
    /**
     * @var PathAnalyzer
     */
    private $pathAnalyzer;

    protected function setUp(): void
    {
        $this->bootKernel(StatieKernel::class);

        $this->pathAnalyzer = self::$container->get(PathAnalyzer::class);
    }

    public function testGetPerex()
    {
        $post = $this->createPostFileFromFileInfo(new SmartFileInfo(__DIR__ . '/PostsSource/2017-06-16-very_short_post.md'));
        $post->addConfiguration(['perex' => 'foo']);
        self::assertSame('foo', $post->getPerex());
        self::assertSame('foo', $post['perex']);
    }

    private function createPostFileFromFileInfo(SmartFileInfo $smartFileInfo): PostFile
    {
        return new PostFile(
            $smartFileInfo->getBasenameWithoutSuffix() . '-1',
            $smartFileInfo,
            $smartFileInfo->getRelativeFilePathFromDirectory(__DIR__ . '/PostsSource'),
            $smartFileInfo->getRealPath(),
            $this->pathAnalyzer->detectFilenameWithoutDate($smartFileInfo),
            $this->pathAnalyzer->detectDate($smartFileInfo)
        );
    }

    public function testHasCode()
    {
        $post = $this->createPostFileFromFileInfo(new SmartFileInfo(__DIR__ . '/PostsSource/2017-06-16-very_short_post.md'));
        $post->addConfiguration(['perex' => 'foo']);
        self::assertSame('foo', $post->getPerex());
        self::assertSame('foo', $post['perex']);
    }

    public function testGetTitle()
    {
        $post = $this->createPostFileFromFileInfo(new SmartFileInfo(__DIR__ . '/PostsSource/2017-06-16-very_short_post.md'));
        $post->addConfiguration(['title' => 'foo']);
        self::assertSame('foo', $post->getTitle());
        self::assertSame('foo', $post['title']);
    }

    /**
     * @param string $file
     * @param int $expectedReadingInMinutes
     * @param string $expectedSmiley
     * @dataProvider provideFileAndReadingTime
     */
    public function testGetReadingComfort(string $file, int $expectedReadingInMinutes, string $expectedSmiley)
    {
        $post = $this->createPostFileFromFileInfo(new SmartFileInfo($file));
        self::assertSame(
            $expectedReadingInMinutes,
            $post->getReadingTimeInMinutes(),
            'Expected different time consumption in minutes'
        );
        self::assertSame(
            $expectedSmiley,
            $post->getReadingTimeSmiley(),
            'Expected different smiley expressing reading comfort'
        );
    }

    public function provideFileAndReadingTime()
    {
        return [
            'very short post' => [
                __DIR__ . '/PostsSource/2017-06-16-very_short_post.md',
                1,
                '😀',
            ],
            'short post' => [
                __DIR__ . '/PostsSource/2018-11-09-short_post.md',
                4,
                '🙂',
            ],
            'long post' => [
                __DIR__ . '/PostsSource/2019-03-21-long_post.md',
                11,
                '😕',
            ],
            'very long post' => [
                __DIR__ . '/PostsSource/2018-08-10-very_long_post.md',
                38,
                '😩',
            ],
        ];
    }

    public function testGetImage()
    {
        $post = $this->createPostFileFromFileInfo(new SmartFileInfo(__DIR__ . '/PostsSource/2017-06-16-very_short_post.md'));
        self::assertNull($post->getImage());
        $post->addConfiguration(['image' => 'rainbow_pony_on_steroids.png']);
        self::assertSame('rainbow_pony_on_steroids.png', $post->getImage());
        self::assertSame('rainbow_pony_on_steroids.png', $post['image']);
    }
}
