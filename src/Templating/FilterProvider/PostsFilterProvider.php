<?php
namespace Symplify\Statie\Templating\FilterProvider;

use Symplify\Statie\Contract\Templating\FilterProviderInterface;
use Symplify\Statie\Generator\Renderable\File\AbstractGeneratorFile;

class PostsFilterProvider implements FilterProviderInterface
{
    public function provide(): array
    {
        return [
            'previous_post' => function (AbstractGeneratorFile $currentPost, array $posts) {
                return $this->findPreviousPost($currentPost, $posts);
            },
            'next_post' => function (AbstractGeneratorFile $currentPost, array $posts) {
                return $this->findNextPost($currentPost, $posts);
            },
        ];
    }

    /**
     * @param AbstractGeneratorFile $currentPost
     * @param array|AbstractGeneratorFile[] $posts
     * @return AbstractGeneratorFile|null
     */
    private function findPreviousPost(AbstractGeneratorFile $currentPost, array $posts): ?AbstractGeneratorFile
    {
        /** @var AbstractGeneratorFile $previousPost */
        $previousPost = null;
        foreach ($posts as $post) {
            $candidate = null;
            if ($post->getDate() < $currentPost->getDate()) {
                $candidate = $previousPost;
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
     * @param AbstractGeneratorFile $currentPost
     * @param array|AbstractGeneratorFile[] $posts
     * @return AbstractGeneratorFile|null
     */
    private function findNextPost(AbstractGeneratorFile $currentPost, array $posts): ?AbstractGeneratorFile
    {
        /** @var AbstractGeneratorFile $nextPost */
        $nextPost = null;
        foreach ($posts as $post) {
            $candidate = null;
            if ($post->getDate() > $currentPost->getDate()) {
                $candidate = $nextPost;
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