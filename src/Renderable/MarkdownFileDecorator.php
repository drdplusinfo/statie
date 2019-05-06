<?php declare(strict_types=1);

namespace Symplify\Statie\Renderable;

use Granam\String\StringTools;
use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;
use ParsedownExtra;
use Symplify\Statie\Contract\Renderable\FileDecoratorInterface;
use Symplify\Statie\Generator\Configuration\GeneratorElement;
use Symplify\Statie\Renderable\File\AbstractFile;

final class MarkdownFileDecorator implements FileDecoratorInterface
{
    /**
     * @var ParsedownExtra
     */
    private $parsedownExtra;

    public function __construct(ParsedownExtra $parsedownExtra)
    {
        $this->parsedownExtra = $parsedownExtra;
        $this->parsedownExtra->setBreaksEnabled(true); // line breaks are kept as <br>
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
        $configuration['perex'] = $this->toSimpleHtml($configuration['perex']);
        $file->addConfiguration($configuration);
    }

    private function toSimpleHtml(string $markdown): string
    {
        $html = $this->parsedownExtra->text($markdown);
        return preg_replace('~^<p>(.*)</p>$~', '$1', $html); //remove unwanted all-wrapping paragraph
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
        /** @var Element $anchor */
        foreach ($anchors as $anchor) {
            if (preg_match(
                '~^(../\d{4}/)(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})-(?<rest>.+)[.]md(?<anchor>#[^#]+)?$~',
                $anchor->getAttribute('href'),
                $matches)
            ) {
                $updatedLink = sprintf('../../../../%s/%s/%s/%s/', $matches['year'], $matches['month'], $matches['day'], $matches['rest']);
                if ($matches['anchor']) {
                    $hashAnchor = str_replace('_', '-', StringTools::toSnakeCaseId($matches['anchor']));
                    $updatedLink .= '#' . $hashAnchor;
                }
                $anchor->setAttribute('href', $updatedLink);
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
