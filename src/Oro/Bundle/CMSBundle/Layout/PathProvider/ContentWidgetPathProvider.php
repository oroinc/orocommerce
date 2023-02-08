<?php

namespace Oro\Bundle\CMSBundle\Layout\PathProvider;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;

/**
 * Builds list of paths which must be processed to find layout updates for a content widget.
 */
class ContentWidgetPathProvider implements PathProviderInterface, ContextAwareInterface
{
    private ThemeManager $themeManager;

    private ContextInterface $context;

    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function getPaths(array $existingPaths): array
    {
        $themeName = $this->context->getOr('theme');
        $contentWidget = $this->context->getOr('content_widget');
        if ($themeName && $contentWidget instanceof ContentWidget && $contentWidget->getWidgetType()) {
            $existingPaths = [];

            $themes = $this->themeManager->getThemesHierarchy($themeName);
            foreach ($themes as $theme) {
                $existingPath = implode(self::DELIMITER, [$theme->getDirectory(), 'content_widget']);

                $existingPaths[] = $existingPath;
                $existingPaths[] = implode(self::DELIMITER, [$existingPath, $contentWidget->getWidgetType()]);
            }
        }

        return $existingPaths;
    }
}
