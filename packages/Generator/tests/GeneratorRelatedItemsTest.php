<?php declare(strict_types=1);

namespace Symplify\Statie\Generator\Tests;

use Symplify\Statie\Generator\RelatedItemsResolver;
use Symplify\Statie\HttpKernel\StatieKernel;

final class GeneratorRelatedItemsTest extends AbstractGeneratorTest
{
    /**
     * @var RelatedItemsResolver
     */
    private $relatedItemsResolver;

    protected function setUp(): void
    {
        $this->bootKernelWithConfigs(StatieKernel::class, [__DIR__ . '/GeneratorSource/statie.yml']);

        parent::setUp();

        $this->relatedItemsResolver = self::$container->get(RelatedItemsResolver::class);
    }

    public function testRelatedItems(): void
    {
        $this->generator->run();
        $posts = $this->statieConfiguration->getOption('posts');
        $postWithRelatedItems = $posts['2017-01-01-1'];

        $relatedItems = $this->relatedItemsResolver->resolveForFile($postWithRelatedItems);

        $this->assertCount(3, $relatedItems);

        $this->assertSame($posts['2017-02-05-1']['title'], $relatedItems[0]['title']);
        $this->assertSame($posts['2017-01-05-2']['title'], $relatedItems[1]['title']);
        $this->assertSame($posts['2017-01-05-1']['title'], $relatedItems[2]['title']);
    }
}
