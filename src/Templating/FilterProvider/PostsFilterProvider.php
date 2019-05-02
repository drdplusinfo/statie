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
            // candidate is older than current post
            $candidate = $this->filesComparator->compare($post, $currentPost, $post->getId(), $currentPost->getId()) < 0
                ? $post
                : null;
            if ($candidate) {
                if (!$previousPost
                    // candidate is newer than previous post
                    || $this->filesComparator->compare($candidate, $previousPost, $candidate->getId(), $previousPost->getId()) > 0
                ) {
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
            // candidate is newer than current post
            $candidate = $this->filesComparator->compare($post, $currentPost, $post->getId(), $currentPost->getId()) > 0
                ? $post
                : null;
            if ($candidate) {
                if (!$nextPost
                    // candidate is older than next post
                    || $this->filesComparator->compare($candidate, $nextPost, $candidate->getId(), $nextPost->getId()) < 0
                ) {
                    $nextPost = $candidate;
                }
            }
        }
        return $nextPost;
    }

}