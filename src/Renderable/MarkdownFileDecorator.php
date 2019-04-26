<?php declare(strict_types=1);

namespace Symplify\Statie\Renderable;

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
        return preg_replace('~^<p>(.*)</p>$~', '$1', $html);
    }

    private function decorateContent(AbstractFile $file): void
    {
        $htmlContent = $this->parsedownExtra->text($file->getContent());

        $file->changeContent($htmlContent);
    }
}
