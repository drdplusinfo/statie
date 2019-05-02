<?php

namespace Symplify\Statie\Tests\Templating\FilterProvider;

use DateTimeInterface;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;
use Symplify\PackageBuilder\Tests\AbstractKernelTestCase;
use Symplify\Statie\Generator\Renderable\File\AbstractGeneratorFile;
use Symplify\Statie\HttpKernel\StatieKernel;
use Symplify\Statie\Renderable\File\PostFile;
use Symplify\Statie\Templating\FilterProvider\PostsFilterProvider;

class PostsFilterProviderTest extends AbstractKernelTestCase
{
    /**
     * @dataProvider providePosts
     * @param AbstractGeneratorFile $currentPost
     * @param array $allPosts
     * @param AbstractGeneratorFile|null $expectedPreviousPost
     * @param AbstractGeneratorFile $expectedNextPost
     * @throws \Exception
     */
    public function test(
        AbstractGeneratorFile $currentPost,
        array $allPosts,
        ?AbstractGeneratorFile $expectedPreviousPost,
        ?AbstractGeneratorFile $expectedNextPost
    ): void
    {
        $this->bootKernel(StatieKernel::class);

        $postsFilterProvider = self::$container->get(PostsFilterProvider::class);
        $previousPostFilter = $postsFilterProvider->provide()['previous_post'];
        $nextPostFilter = $postsFilterProvider->provide()['next_post'];

        /** @var AbstractGeneratorFile $filteredPreviousPost */
        $filteredPreviousPost = $previousPostFilter($allPosts, $currentPost);
        $this->assertSame(
            $expectedPreviousPost,
            $filteredPreviousPost,
            $expectedPreviousPost === null
                ? 'Expected null as a previous post'
                : sprintf(
                'Expected previous post %s, got %s',
                $expectedPreviousPost->getDateInFormat(DATE_ATOM) . ' #' . $expectedPreviousPost->getId(),
                $filteredPreviousPost === null
                    ? 'null'
                    : $filteredPreviousPost->getDateInFormat(DATE_ATOM) . ' #' . $filteredPreviousPost->getId()
            )
        );
        /** @var AbstractGeneratorFile $filteredNextPost */
        $filteredNextPost = $nextPostFilter($allPosts, $currentPost);
        $this->assertSame(
            $expectedNextPost,
            $filteredNextPost,
            $expectedNextPost === null
                ? 'Expected null as a next post'
                : sprintf(
                'Expected next post with date %s, got %s',
                $expectedNextPost->getDateInFormat(DATE_ATOM) . ' #' . $expectedNextPost->getId(),
                $filteredNextPost === null
                    ? 'null'
                    : $filteredNextPost->getDateInFormat(DATE_ATOM) . ' #' . $filteredNextPost->getId()
            )
        );
    }

    public function providePosts(): array
    {
        return [
            'single post' => [$this->createPostFile('2019-01-02-1', new \DateTime('2019-01-02')), [], null, null],
            'only previous post' => [
                $this->createPostFile('2019-01-02-1', new \DateTime('2019-01-02')),
                [$onlyPreviousPost = $this->createPostFile('2019-01-01-1', new \DateTime('2019-01-01'))],
                $onlyPreviousPost,
                null,
            ],
            'only next post' => [
                $this->createPostFile('2019-01-02-1', new \DateTime('2019-01-02')),
                [$onlyNextPost = $this->createPostFile('2019-01-03-1', new \DateTime('2019-01-03'))],
                null,
                $onlyNextPost,
            ],
            'more previous and next posts' => [
                $this->createPostFile('2019-01-02-2', new \DateTime('2019-01-02')),
                [
                    $this->createPostFile('2018-01-01-2', new \DateTime('2018-01-01')),
                    $this->createPostFile('2018-01-01-1', new \DateTime('2018-01-01')),
                    $this->createPostFile('2018-05-05-1', new \DateTime('2018-05-05')),
                    $closestPreviousPost = $this->createPostFile('2019-01-02-1', new \DateTime('2019-01-02')),
                    $closestNextPost = $this->createPostFile('2019-06-06-1', new \DateTime('2019-06-06')),
                    $this->createPostFile('2019-06-06-2', new \DateTime('2019-06-06')),
                ],
                $closestPreviousPost,
                $closestNextPost,
            ],
        ];
    }

    private function createPostFile(
        string $id,
        DateTimeInterface $dateTime,
        SmartFileInfo $smartFileInfo = null,
        string $relativeSource = null,
        string $filePath = null,
        string $filenameWithoutDate = null
    )
    {
        return new PostFile(
            $id,
            $smartFileInfo ?? new SmartFileInfo(__FILE__),
            $relativeSource ?? 'foo',
            $filePath ?? 'bar',
            $filenameWithoutDate ?? 'baz',
            $dateTime ?? new \DateTime()
        );
    }
}
