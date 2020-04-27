<?php declare(strict_types=1);

namespace Symplify\Statie\Latte;

use Latte\Engine;
use Latte\ILoader;
use Latte\Loader;
use Symplify\Statie\Contract\Templating\FilterProviderInterface;

final class LatteFactory
{
    /**
     * @var FilterProviderInterface[]
     */
    private $filterProviders;

    /**
     * @var ILoader
     */
    private $loader;

    /**
     * @param Loader $loader
     * @param FilterProviderInterface[] $filterProviders
     */
    public function __construct(Loader $loader, array $filterProviders)
    {
        $this->loader = $loader;
        $this->filterProviders = $filterProviders;
    }

    public function create(): Engine
    {
        $latteEngine = new Engine();
        $latteEngine->setLoader($this->loader);
        $latteEngine->setTempDirectory(sys_get_temp_dir() . '/_flat_white_latte_factory_cache');

        foreach ($this->filterProviders as $filterProvider) {
            foreach ($filterProvider->provide() as $name => $filter) {
                $latteEngine->addFilter($name, $filter);
            }
        }

        return $latteEngine;
    }
}
