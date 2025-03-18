<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Twig\Environment;

/**
 * Layout data provider for Content Widgets.
 * Adds possibility to get appropriate data and template according given Content Widget entity.
 */
class ContentWidgetDataProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private ContentWidgetTypeRegistry $contentWidgetTypeRegistry,
        private Environment $twig,
        private ManagerRegistry $doctrine,
        private ThemeConfigurationProvider $themeConfigurationProvider,
    ) {
    }

    public function getContentWidgetNameByThemeConfigKey(string $key): string
    {
        return $this->getContentWidgetName($key);
    }

    public function getWidgetData(ContentWidget $contentWidget): array
    {
        $contentWidgetType = $this->getType($contentWidget);

        return $contentWidgetType ? $contentWidgetType->getWidgetData($contentWidget) : [];
    }

    public function hasContentWidget(string $name): bool
    {
        return null !== $this->getContentWidgetByName($name);
    }

    public function getDefaultTemplate(ContentWidget $contentWidget): string
    {
        $contentWidgetType = $this->getType($contentWidget);

        return $contentWidgetType ? $contentWidgetType->getDefaultTemplate($contentWidget, $this->twig) : '';
    }

    private function getContentWidgetName(string $key): string
    {
        $configValue = $this->themeConfigurationProvider->getThemeConfigurationOption($key);
        if (!$configValue) {
            return '';
        }

        $contentWidget = $this->doctrine->getRepository(ContentWidget::class)->find($configValue);

        return $contentWidget?->getName() ?? '';
    }

    private function getType(ContentWidget $contentWidget): ?ContentWidgetTypeInterface
    {
        $contentWidgetType = $this->contentWidgetTypeRegistry->getWidgetType($contentWidget->getWidgetType());
        if (!$contentWidgetType && $this->logger) {
            $this->logger->error(
                sprintf('Content widget type %s is not registered', $contentWidget->getWidgetType())
            );
        }

        return $contentWidgetType;
    }

    private function getContentWidgetByName(string $name): ?ContentWidget
    {
        return $this->doctrine->getRepository(ContentWidget::class)->findOneBy(['name' => $name]);
    }
}
