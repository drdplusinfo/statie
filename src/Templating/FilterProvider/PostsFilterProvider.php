<?php
namespace Symplify\Statie\Templating\FilterProvider;

use Symplify\Statie\Contract\Templating\FilterProviderInterface;
use Symplify\Statie\Generator\Renderable\File\AbstractGeneratorFile;

class PostsFilterProvider implements FilterProviderInterface
{
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
            $candidate = null;
            if ($post->getDate() < $currentPost->getDate()) {
                $candidate = $post;
            } elseif ($post->getDate() == $currentPost->getDate() && $post->getId() < $currentPost->getId()) {
                $candidate = $post;
            }
            if ($candidate && (!$previousPost || $previousPost->getDate() > $candidate->getDate())) {
                $previousPost = $candidate;
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
            $candidate = null;
            if ($post->getDate() > $currentPost->getDate()) {
                $candidate = $post;
            } elseif ($post->getDate() == $currentPost->getDate() && $post->getId() > $currentPost->getId()) {
                $candidate = $post;
            }
            if ($candidate && (!$nextPost || $nextPost->getDate() > $candidate->getDate())) {
                $nextPost = $candidate;
            }
        }
        return $nextPost;
    }

}