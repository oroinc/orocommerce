<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Psr\Log\LoggerInterface;

/**
 * Renders a content widget.
 */
class ContentWidgetRenderer
{
    private const ERROR_TEMPLATE = <<<HTML
<div class="alert alert-error alert--compact" role="alert">
    <span class="fa-exclamation alert-icon" aria-hidden="true"></span>
    Rendering of the content widget "%s" failed: %s
</div>
HTML;

    private ContentWidgetProvider $contentWidgetProvider;
    private ContentWidgetTypeRegistry $contentWidgetTypeRegistry;
    private LayoutManager $layoutManager;
    private LoggerInterface $logger;
    private bool $debug;

    public function __construct(
        ContentWidgetProvider $contentWidgetProvider,
        ContentWidgetTypeRegistry $contentWidgetTypeRegistry,
        LayoutManager $layoutManager,
        LoggerInterface $logger,
        bool $debug,
    ) {
        $this->contentWidgetProvider = $contentWidgetProvider;
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
        $this->layoutManager = $layoutManager;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function render(string $widgetName): string
    {
        try {
            $widget = $this->contentWidgetProvider->getContentWidget($widgetName);

            return $this->getWidgetLayoutBuilder()
                ->getLayout($this->getWidgetLayoutContext($this->getWidgetType($widget), $widget))
                ->render();
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Error occurred while rendering content widget "%s".', $widgetName),
                ['exception' => $e]
            );
            if ($this->debug) {
                return sprintf(self::ERROR_TEMPLATE, $widgetName, $e->getMessage());
            }
        }

        return '';
    }

    protected function getWidgetType(ContentWidget $widget): ?ContentWidgetTypeInterface
    {
        $type = $this->contentWidgetTypeRegistry->getWidgetType($widget->getWidgetType());
        if (null === $type) {
            throw new \RuntimeException(sprintf(
                'The context widget type "%s" does not exist.',
                $widget->getWidgetType()
            ));
        }

        return $type;
    }

    private function getWidgetLayoutBuilder(): LayoutBuilderInterface
    {
        $layoutBuilder = $this->layoutManager->getLayoutBuilder();
        $layoutBuilder->add('content_widget_root', null, 'content_widget_root');

        return $layoutBuilder;
    }

    private function getWidgetLayoutContext(
        ContentWidgetTypeInterface $type,
        ContentWidget $widget
    ): LayoutContext {
        return new LayoutContext(
            ['data' => $type->getWidgetData($widget), 'content_widget' => $widget],
            ['content_widget']
        );
    }
}
