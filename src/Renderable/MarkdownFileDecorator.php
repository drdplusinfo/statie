<?php declare(strict_types=1);

namespace Symplify\Statie\Renderable;

use Granam\String\StringTools;
use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;
use ParsedownExtra;
use Symplify\Statie\Configuration\OptionsInterface;
use Symplify\Statie\Contract\Renderable\FileDecoratorInterface;
use Symplify\Statie\Generator\Configuration\GeneratorElement;
use Symplify\Statie\Renderable\File\AbstractFile;

final class MarkdownFileDecorator implements FileDecoratorInterface
{
    /**
     * @var ParsedownExtra
     */
    private $parsedownExtra;
    /**
     * @var OptionsInterface
     */
    private $options;

    public function __construct(ParsedownExtra $parsedownExtra, OptionsInterface $options)
    {
        $this->parsedownExtra = $parsedownExtra;
        $this->parsedownExtra->setBreaksEnabled(true); // line breaks are kept as <br>
        $this->options = $options;
    }

    /**
     * @param AbstractFile[] $files
     * @return AbstractFile[]
     */
    public function decorateFiles(array $files): array
    {
        foreach ($files as $file) {
            $this->decorateFile($file);
        }

        return $files;
    }

    /**
     * @param AbstractFile[] $files
     * @param GeneratorElement $generatorElement
     * @return AbstractFile[]
     */
    public function decorateFilesWithGeneratorElement(array $files, GeneratorElement $generatorElement): array
    {
        return $this->decorateFiles($files);
    }

    /**
     * Higher priorities are executed first.
     *
     * Has to run before Latte; it fails the other way.
     */
    public function getPriority(): int
    {
        return 800;
    }

    private function decorateFile(AbstractFile $file): void
    {
        // skip due to HTML content incompatibility
        if ($file->getExtension() !== 'md') {
            return;
        }

        $this->decorateTitle($file);
        $this->decoratePerex($file);
        $this->decorateImageAuthor($file);
        $this->decorateContent($file);
    }

    private function decorateTitle(AbstractFile $file): void
    {
        $configuration = $file->getConfiguration();
        if (($configuration['title'] ?? '') === '') {
            return;
        }
        $configuration['title'] = $this->toSimpleHtml($configuration['title']);
        $file->addConfiguration($configuration);
    }

    private function decoratePerex(AbstractFile $file): void
    {
        $configuration = $file->getConfiguration();
        if (($configuration['perex'] ?? '') === '') {
            return;
        }
        $perextTextContent = $this->toSimpleHtml($configuration['perex']);
        $imageHtml = '';
        $imageWidth = null;
        $imageHeight = null;
        if (!empty($configuration['image'])) {
            ['imageWidth' => $imageWidth, 'imageHeight' => $imageHeight] = $this->getImageDimensions($configuration['image']);
            $imageHtml = $this->getImageForPerex($configuration['image'], $configuration['title'] ?? '', $configuration['image_title'] ?? '', $imageWidth, $imageHeight);
        }
        $configuration['perex'] = $imageWidth && $imageHeight && $imageWidth / $imageHeight > 1.3
            ? <<<HTML
<span class="row">
    <span class="col-lg perex-image-container align-self-center">$imageHtml</span>
    <span class="col-lg">$perextTextContent</span>
</span>
HTML
            : <<<HTML
<span class="row">
    <span class="col-lg-4 col-sm perex-image-container align-self-center">$imageHtml</span>
    <span class="col-lg-8 col-sm">$perextTextContent</span>
</span>
HTML;
        $file->addConfiguration($configuration);
    }

    private function getImageDimensions(string $imageLink): array
    {
        $imageRelativePath = substr($imageLink, 0, strpos($imageLink, '?') ?: strlen($imageLink));
        $assetsRootDir = $this->options->getOption('assets')['root_dir'] ?? '';
        $imagePath = $assetsRootDir . $imageRelativePath;
        if (!is_readable($imagePath)) {
            throw new Exceptions\InvalidImagePathException(sprintf(
                "No readable file has been found on path '%s' built from image link '%s'%s%s",
                $imagePath,
                $imageLink,
                $assetsRootDir !== ''
                    ? strpos(" and assets root dir '%s'", $assetsRootDir)
                    : " (try to set asset root dir in config.yml via options assets root_dir)",
                strpos($imagePath, '.') === 0 ? sprintf(" with current working directory '%s'", getcwd()) : ''
            ));
        }
        [$imageWidth, $imageHeight] = getimagesize($imagePath) ?: [null, null];

        return ['imageWidth' => $imageWidth, 'imageHeight' => $imageHeight];
    }

    private function toSimpleHtml(string $markdown): string
    {
        $html = $this->parsedownExtra->text($markdown);
        return preg_replace('~^<p>(.*)</p>$~', '$1', $html); //remove unwanted all-wrapping paragraph
    }

    private function getImageForPerex(string $image, string $alternative, string $title, ?int $imageWidth, ?int $imageHeight): string
    {
        $escapedAlternative = htmlentities($alternative);
        $escapedTitle = htmlentities($title);
        $widthAndHeight = $imageWidth && $imageHeight
            ? sprintf('width="%d" height="%d"', $imageWidth, $imageHeight)
            : '';
        return <<<HTML
<img src="{$image}" alt="{$escapedAlternative}" title="{$escapedTitle}" {$widthAndHeight}>
HTML;
    }

    private function decorateImageAuthor(AbstractFile $file)
    {
        $configuration = $file->getConfiguration();
        if (($configuration['image_author'] ?? '') === '') {
            return;
        }
        $configuration['image_author'] = $this->toSimpleHtml($configuration['image_author']);
        $file->addConfiguration($configuration);
    }

    private function decorateContent(AbstractFile $file): void
    {
        $htmlContent = $this->parsedownExtra->text($file->getContent());
        $htmlContent = $this->modifyLocalLinks($htmlContent);

        $file->changeContent($htmlContent);
    }

    private function modifyLocalLinks(string $html): string
    {
        $bodyAdded = true;
        if (strpos($html, '<body>') === false) {
            $html = "<body>$html</body>";
        }
        $document = new HTMLDocument(<<<HTML
<!DOCTYPE html>
<html lang="cs">
{$html}
</html>
HTML
        );
        $anchors = $document->getElementsByTagName('a');
        $routePrefix = $this->options->getOption('generators')['posts']['route_prefix'] ?? null;
        /** @var Element $anchor */
        foreach ($anchors as $anchor) {
            if (preg_match(
                '~^([.][.]/\d{4}/)?(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})-(?<rest>.+)[.]md(#(?<anchor>[^#]+))?$~',
                $anchor->getAttribute('href'),
                $matches)
            ) {
                if ($routePrefix !== null) {
                    $updatedLink = '/' . str_replace([':year', ':month', ':day'], [$matches['year'], $matches['month'], $matches['day']], $routePrefix);
                    $updatedLink .= '/' . $matches['rest'] . '/';
                } else {
                    $updatedLink = sprintf('../../../../%s/%s/%s/%s/', $matches['year'], $matches['month'], $matches['day'], $matches['rest']);
                }
                if (!empty($matches['anchor'])) {
                    $hashAnchor = str_replace('_', '-', StringTools::toSnakeCaseId($matches['anchor']));
                    $updatedLink .= '#' . $hashAnchor;
                }
                $anchor->setAttribute('href', $updatedLink);
            } elseif (preg_match('~^#(?<anchor>[^#]+)$~', $anchor->getAttribute('href'), $matches)) {
                $hashAnchor = str_replace('_', '-', StringTools::toSnakeCaseId($matches['anchor']));
                $anchor->setAttribute('href', '#' . $hashAnchor);
            }
        }
        if ($bodyAdded) {
            $htmlNode = $document->body;
        } else {
            $htmlNode = $document->getElementsByTagName('html')[0];
        }
        return $htmlNode->prop_get_innerHTML();
    }
}
