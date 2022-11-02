<?php

namespace Oro\Bundle\CMSBundle\ContentBlock;

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

    private const TEMPLATE = '@OroCMS/ContentBlock/widget.html.twig';

    /** @var ContentBlockDataProvider */
    private $contentBlockDataProvider;

    /** @var Environment */
    private $twig;

    /** @var array */
    private $aliases = [];

    /** @var Template|null */
    private $template;

    public function __construct(ContentBlockDataProvider $contentBlockDataProvider, Environment $twig)
    {
        $this->contentBlockDataProvider = $contentBlockDataProvider;
        $this->twig = $twig;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function render(string $blockAlias): string
    {
        $content = '';

        try {
            if (isset($this->aliases[$blockAlias])) {
                throw new \RuntimeException(sprintf('Found a recursive "%s" content block renderer', $blockAlias));
            }

            $contentBlockView = $this->contentBlockDataProvider->getContentBlockView($blockAlias);
            if (!$contentBlockView) {
                throw new \RuntimeException(sprintf('Could not find content block "%s"', $blockAlias));
            }

            $this->aliases[$blockAlias] = true;
            $content = $this->twig->render(self::TEMPLATE, ['contentBlock' => $contentBlockView]);
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Error occurred while rendering content block %s', $blockAlias),
                ['exception' => $exception]
            );
        }

        unset($this->aliases[$blockAlias]);

        return $content;
    }
}
