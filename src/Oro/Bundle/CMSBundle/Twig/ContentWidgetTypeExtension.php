<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig filter to render a content widget type label:
 *   - content_widget_type_label
 */
class ContentWidgetTypeExtension extends AbstractExtension
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /**
     * @param TranslatorInterface $translator
     * @param ContentWidgetTypeRegistry $contentWidgetTypeRegistry
     */
    public function __construct(TranslatorInterface $translator, ContentWidgetTypeRegistry $contentWidgetTypeRegistry)
    {
        $this->translator = $translator;
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('content_widget_type_label', [$this, 'getContentWidgetTypeLabel']),
        ];
    }

    /**
     * @param string $widgetType
     *
     * @return string
     */
    public function getContentWidgetTypeLabel(string $widgetType): string
    {
        if (!$widgetType) {
            return $widgetType;
        }

        $contentWidgetType = $this->contentWidgetTypeRegistry->getWidgetType($widgetType);

        return $contentWidgetType ? $this->translator->trans($contentWidgetType->getLabel()) : $widgetType;
    }
}
