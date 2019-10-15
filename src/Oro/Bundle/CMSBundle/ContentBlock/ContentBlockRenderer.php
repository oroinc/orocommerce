<?php

namespace Oro\Bundle\CMSBundle\ContentBlock;

use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Layout\DataProvider\ContentBlockDataProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Environment;
use Twig\Template;

/**
 * Renders content block.
 */
class ContentBlockRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ContentBlockDataProvider */
    private $contentBlockDataProvider;

    /** @var Environment */
    private $twig;

    /** @var array */
    private $aliases = [];

    /** @var Template|null */
    private $template;

    /**
     * @param ContentBlockDataProvider $contentBlockDataProvider
     * @param Environment $twig
     */
    public function __construct(ContentBlockDataProvider $contentBlockDataProvider, Environment $twig)
    {
        $this->contentBlockDataProvider = $contentBlockDataProvider;
        $this->twig = $twig;
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $blockAlias
     * @return string
     */
    public function render(string $blockAlias): string
    {
        if (isset($this->aliases[$blockAlias])) {
            throw new \RuntimeException(sprintf('Found a recursive "%s" content block renderer', $blockAlias));
        }

        $contentBlockView = $this->contentBlockDataProvider->getContentBlockView($blockAlias);
        if (!$contentBlockView) {
            $this->logger->error(sprintf('Could not render content block %s: cannot find content block', $blockAlias));

            return '';
        }

        $this->aliases[$blockAlias] = true;
        $content = $this->renderBlock($contentBlockView);
        unset($this->aliases[$blockAlias]);

        return $content;
    }

    /**
     * @param ContentBlockView $contentBlockView
     * @return string
     */
    private function renderBlock(ContentBlockView $contentBlockView): string
    {
        try {
            $content = $this->getTemplate()->render(['contentBlock' => $contentBlockView]);
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Error occurred while rendering content block %s', $contentBlockView->getAlias()),
                ['exception' => $exception]
            );

            $content = '';
        }

        return $content;
    }

    /**
     * @return Template
     */
    private function getTemplate(): Template
    {
        if (!$this->template) {
            $this->template = $this->twig->loadTemplate('@OroCMS/ContentBlock/widget.html.twig');
        }

        return $this->template;
    }
}
