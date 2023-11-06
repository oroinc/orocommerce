<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Twig\Environment;

/**
 * Renders a Wysiwyg widget icon.
 */
class WysiwygWidgetIconRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
        $this->logger = new NullLogger();
    }

    public function render(string $widgetName, array $options = []): string
    {
        $options['name'] = $widgetName;
        $template = $this->getTemplate();
        try {
            return $this->twig->render($template, ['options' => $options]);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Could not render widget icon "{iconId}"',
                ['exception' => $exception, 'iconId' => $widgetName, 'options' => $options]
            );
        }

        return '';
    }

    protected function getTemplate(): string
    {
        return '@OroCMS/Wysiwyg/widget_icon.html.twig';
    }
}
