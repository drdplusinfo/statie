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
        self::assertNull($post->getImageTitle());
        $post->addConfiguration(['image' => 'rainbow_pony_on_steroids.png', 'image_title' => 'Wanna fly?', 'image_author' => 'Nature']);
        self::assertSame('rainbow_pony_on_steroids.png', $post->getImage());
        self::assertSame('rainbow_pony_on_steroids.png', $post['image']);
        self::assertSame('Wanna fly?', $post->getImageTitle());
        self::assertSame('Wanna fly?', $post['image_title']);
        self::assertSame('Nature', $post->getImageAuthor());
        self::assertSame('Nature', $post['image_author']);
    }

    public function testGetPostImages()
    {
        $post = $this->createPostFileFromFileInfo(new SmartFileInfo(__DIR__ . '/PostsSource/2020-06-18-with-images.md'));
        $postImages = $post->getPostImages();
        $expectedImages = [
            '/assets/images/posts/kouzelnik_philip_ward/philip_ward_navrhy_drd.png?version=bd3d8ab55bb436b272d67753ec1a222b',
            '/assets/images/posts/kouzelnik_philip_ward/senkyr_prijima_zalohu_za_veselku.png?version=9e1984021aed1a1d9143b7882db8c840',
            '/assets/images/posts/kouzelnik_philip_ward/senkyr_specha_na_schuzku_se_starostou.png?version=369d93240818df60efaf73a2e54208d1',
            '/assets/images/posts/kouzelnik_philip_ward/narvano_v_senku.png?version=b908397c39a5a49ae3f37cd2ff38b516',
            '/assets/images/posts/kouzelnik_philip_ward/sluzebna_anna_nestiha_kuchyn_a_hosty_dohromady.png?version=5b60658e1dc612fe5666a3aaa1a5d9c3',
            '/assets/images/posts/kouzelnik_philip_ward/prcek_se_diva_do_hlubin_sklepeni.png?version=37a41b4e7e029c308e53532a9468f51b',
            '/assets/images/posts/kouzelnik_philip_ward/prcek_se_odhodlava_vstoupit_do_sklepa.png?version=9b0f978292d34604752d97448b3c17e6',
            '/assets/images/posts/kouzelnik_philip_ward/strach_ma_velke_oci.png?version=fa1dd27de48dc32f164c6f1588e2b559',
            '/assets/images/posts/kouzelnik_philip_ward/brutopyr_zelvorec_houbeles_1.0.png?version=51809e19e9b8c9be464378e2f5caa953',
            '/assets/images/posts/kouzelnik_philip_ward/brutopyr_zelvorec_2.0.png?version=246aca6d37b5a7574c8f67d723446ea1',
            '/assets/images/posts/kouzelnik_philip_ward/brutopyr_zelvorec_3.0.png?version=79425398d43bd3541822438c36c46a4d',
            '/assets/images/posts/kouzelnik_philip_ward/brutopyr_zelvorec_4.0.png?version=98fa35140d9e0f59a42098a555fa46ec',
            '/assets/images/posts/kouzelnik_philip_ward/zelvorec_5.0.png?version=1428827aa22b051df6575727d6f2f38d',
            '/assets/images/posts/kouzelnik_philip_ward/zelvorec_6.0.png?version=3940abf424142e4f827092af637af75f',
            '/assets/images/posts/kouzelnik_philip_ward/houbeles_2.0.png?version=d2f6e9c3754cf920c79b2c9eec832cc9',
            '/assets/images/posts/kouzelnik_philip_ward/houbeles_2.1.png?version=7a2053490cc9028bd992a0677b0e08ca',
            '/assets/images/posts/kouzelnik_philip_ward/houbeles_2.1.1.png?version=a237df69ad4a471dc0d153d2ae793ccf',
            '/assets/images/posts/kouzelnik_philip_ward/brutopyr_5.0.png?version=8c9d8e745a36e992a6b0fd43041d093e',
            '/assets/images/posts/kouzelnik_philip_ward/brutopyr_5.1.png?version=abe8d1e305b5b25166176499d2af8dfc',
            '/assets/images/posts/kouzelnik_philip_ward/brutopyr_5.1.1.png?version=00283caf22c5a34c69bb401f117e461e',
        ];
        sort($postImages);
        sort($expectedImages);
        self::assertSame($expectedImages, $postImages);
    }
}
