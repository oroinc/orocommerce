<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Bundle\LayoutBundle\Layout\TwigEnvironmentAwareLayoutRendererInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Renders a content widget.
 */
class ContentWidgetRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const ERROR_TEMPLATE = <<<HTML
<div class="alert alert-error alert--compact" role="alert">
    <span class="fa-exclamation alert-icon" aria-hidden="true"></span>
    Rendering of the content widget "%s" failed: %s
</div>
HTML;

    private ContentWidgetProvider $contentWidgetProvider;
    private ContentWidgetTypeRegistry $contentWidgetTypeRegistry;
    private LayoutManager $layoutManager;
    private FrontendHelper $frontendHelper;
    private FrontendEmulator $frontendEmulator;
    private bool $debug;

    public function __construct(
        ContentWidgetProvider $contentWidgetProvider,
        ContentWidgetTypeRegistry $contentWidgetTypeRegistry,
        LayoutManager $layoutManager,
        FrontendHelper $frontendHelper,
        FrontendEmulator $frontendEmulator,
        LoggerInterface $logger,
        bool $debug,
    ) {
        $this->contentWidgetProvider = $contentWidgetProvider;
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
        $this->layoutManager = $layoutManager;
        $this->frontendHelper = $frontendHelper;
        $this->frontendEmulator = $frontendEmulator;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function render(string $widgetName): string
    {
        if (!$this->frontendHelper->isFrontendRequest()) {
            $this->frontendEmulator->startFrontendRequestEmulation();
            try {
                return $this->renderWidget($widgetName);
            } finally {
                $this->frontendEmulator->stopFrontendRequestEmulation();
            }
        }

        return $this->renderWidget($widgetName);
    }

    private function renderWidget(string $widgetName): string
    {
        try {
            $layoutFactory = $this->layoutManager->getLayoutFactory();
            $twigLayoutRenderer = $layoutFactory->getRendererRegistry()->getRenderer();
            if ($twigLayoutRenderer instanceof TwigEnvironmentAwareLayoutRendererInterface) {
                $originalEnv = $twigLayoutRenderer->getEnvironment();
                // Switches to the temporary twig environment before a content widget is rendered to ensure
                // that previous rendering will not affect the current rendering process. Solves the problem with
                // TWIG environment local caching that prevents the nested content widgets from correct rendering.
                $twigLayoutRenderer->setEnvironment(clone $originalEnv);
            }

            return $this->doRender($widgetName, $layoutFactory);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('Error occurred while rendering content widget "%s".', $widgetName),
                ['exception' => $e]
            );

            if ($this->debug) {
                return sprintf(self::ERROR_TEMPLATE, $widgetName, $e->getMessage());
            }
        } finally {
            if (isset($twigLayoutRenderer, $originalEnv)) {
                // Restores the original twig environment after a content widget is rendered.
                $twigLayoutRenderer->setEnvironment($originalEnv);
            }
        }

        return '';
    }

    private function doRender(string $widgetName, LayoutFactoryInterface $layoutFactory): string
    {
        $layoutBuilder = $layoutFactory->createLayoutBuilder();
        // Adds a root block required for all content widgets.
        $layoutBuilder->add('content_widget_root', null, 'content_widget_root');

        $widget = $this->contentWidgetProvider->getContentWidget($widgetName);
        $contentWidgetType = $this->getWidgetType($widget);
        $layoutContext = $this->getWidgetLayoutContext($contentWidgetType, $widget);

        return $layoutBuilder->getLayout($layoutContext)->render();
    }

    private function getWidgetType(ContentWidget $widget): ContentWidgetTypeInterface
    {
        $type = $this->contentWidgetTypeRegistry->getWidgetType($widget->getWidgetType());
        if (null === $type) {
            throw new \RuntimeException(
                sprintf(
                    'The context widget type "%s" does not exist.',
                    $widget->getWidgetType()
                )
            );
        }

        return $type;
    }

    private function getWidgetLayoutContext(ContentWidgetTypeInterface $type, ContentWidget $widget): LayoutContext
    {
        return new LayoutContext(
            ['data' => $type->getWidgetData($widget), 'content_widget' => $widget],
            ['content_widget']
        );
    }
}
