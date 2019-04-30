<?php
namespace Symplify\Statie\Templating\FilterProvider;

use Symplify\Statie\Contract\Templating\FilterProviderInterface;
use Symplify\Statie\Generator\FilesComparator;
use Symplify\Statie\Generator\Renderable\File\AbstractGeneratorFile;

class PostsFilterProvider implements FilterProviderInterface
{
    /**
     * @var FilesComparator
     */
    private $filesComparator;

    public function __construct(FilesComparator $filesComparator)
    {
        $this->filesComparator = $filesComparator;
    }

    public function provide(): array
    {
        return [
            'previous_post' => function (array $posts, AbstractGeneratorFile $currentPost) {
                return $this->findPreviousPost($posts, $currentPost);
            },
            'next_post' => function (array $posts, AbstractGeneratorFile $currentPost) {
                return $this->findNextPost($posts, $currentPost);
            },
        ];
    }

    /**
     * @param array|AbstractGeneratorFile[] $posts
     * @param AbstractGeneratorFile $currentPost
     * @return AbstractGeneratorFile|null
     */
    private function findPreviousPost(array $posts, AbstractGeneratorFile $currentPost): ?AbstractGeneratorFile
    {
        /** @var AbstractGeneratorFile $previousPost */
        $previousPost = null;
        foreach ($posts as $post) {
            $candidate = $this->filesComparator->compare($post, $currentPost, $post->getId(), $currentPost->getId()) < 0
                ? $post
                : null;
            if ($candidate) {
                if (!$previousPost || $this->filesComparator->compare($previousPost, $candidate) < 0) { // candidate is newer than previous post
                    $previousPost = $candidate;
                }
            }
        }
        return $previousPost;
    }

    /**
     * @param array|AbstractGeneratorFile[] $posts
     * @param AbstractGeneratorFile $currentPost
     * @return AbstractGeneratorFile|null
     */
    private function findNextPost(array $posts, AbstractGeneratorFile $currentPost): ?AbstractGeneratorFile
    {
        /** @var AbstractGeneratorFile $nextPost */
        $nextPost = null;
        foreach ($posts as $post) {
            $candidate = $this->filesComparator->compare($post, $currentPost, $post->getId(), $currentPost->getId()) > 0
                ? $post
                : null;
            if ($candidate) {
                if (!$nextPost || $this->filesComparator->compare($nextPost, $candidate) > 0) { // candidate is older than next post
                    $nextPost = $candidate;
                }
            }
        }
        return $nextPost;
    }

}