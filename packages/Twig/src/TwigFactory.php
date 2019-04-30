<?php declare(strict_types=1);

namespace Symplify\Statie\Twig;

use Latte\Runtime\FilterExecutor;
use Symplify\Statie\Contract\Templating\FilterProviderInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig_Filter;
use Twig_Function;

final class TwigFactory
{
    /**
     * @var string
     */
    private $twigCacheDirectory;

    /**
     * @var FilterProviderInterface[]
     */
    private $filterProviders = [];

    /**
     * @var LoaderInterface
     */
    private $arrayLoader;

    /**
     * @var FilterExecutor
     */
    private $filterExecutor;

    /**
     * @param FilterProviderInterface[] $filterProviders
     */
    public function __construct(
        ArrayLoader $arrayLoader,
        string $twigCacheDirectory,
        FilterExecutor $filterExecutor,
        array $filterProviders
    ) {
        $this->arrayLoader = $arrayLoader;
        $this->twigCacheDirectory = $twigCacheDirectory;
        $this->filterExecutor = $filterExecutor;
        $this->filterProviders = $filterProviders;
    }

    public function create(): Environment
    {
        $debug = ($_ENV['APP_ENV'] ?? '') === 'dev';
        $twigEnvironment = new Environment($this->arrayLoader, [
            'cache' => $this->twigCacheDirectory,
            'debug' => $debug,
        ]);
        if ($debug) {
            $twigEnvironment->addExtension(new DebugExtension());
        }

        // report missing variables, it's easier to debug code then in case of typo
        $twigEnvironment->enableStrictVariables();

        $this->loadLatteFilters($twigEnvironment);

        foreach ($this->filterProviders as $filterProvider) {
            foreach ($filterProvider->provide() as $name => $filter) {
                $twigEnvironment->addFilter(new TwigFilter($name, $filter));
                $twigEnvironment->addFunction(new TwigFunction($name, $filter));
            }
        }

        return $twigEnvironment;
    }

    /**
     * @see https://github.com/nette/latte/blob/edcda892aee632c810697d9795c4fb065cd51506/src/Latte/Runtime/FilterExecutor.php
     */
    private function loadLatteFilters(Environment $twigEnvironment): void
    {
        foreach ($this->filterExecutor->getAll() as $name => $filter) {
            $twigEnvironment->addFilter(new Twig_Filter($name, $this->filterExecutor->{$filter}));
            $twigEnvironment->addFunction(new Twig_Function($name, $this->filterExecutor->{$filter}));
        }
    }
}
