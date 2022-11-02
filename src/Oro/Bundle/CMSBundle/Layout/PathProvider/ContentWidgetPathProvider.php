<?php

namespace Oro\Bundle\CMSBundle\Layout\PathProvider;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;

/**
 * Builds list of paths which must be processed to find layout updates.
 */
class ContentWidgetPathProvider implements PathProviderInterface, ContextAwareInterface
{
    /** @var ThemeManager */
    protected $themeManager;

    /** @var ContextInterface */
    protected $context;

    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaths(array $existingPaths): array
    {
        $themeName = $this->context->getOr('theme');
        $contentWidget = $this->context->getOr('content_widget');
        if ($themeName && $contentWidget instanceof ContentWidget && $contentWidget->getWidgetType()) {
            $existingPaths = [];

            $themes = $this->getThemesHierarchy($themeName);
            foreach ($themes as $theme) {
                $existingPath = implode(self::DELIMITER, [$theme->getDirectory(), 'content_widget']);

                $existingPaths[] = $existingPath;
                $existingPaths[] = implode(self::DELIMITER, [$existingPath, $contentWidget->getWidgetType()]);
            }
        }

        return $existingPaths;
    }

    /**
     * Returns theme inheritance hierarchy with root theme as first item
     *
     * @param string $themeName
     *
     * @return Theme[]
     */
    protected function getThemesHierarchy($themeName): array
    {
        $hierarchy = [];

        while (null !== $themeName) {
            $theme = $this->themeManager->getTheme($themeName);

            $hierarchy[] = $theme;
            $themeName   = $theme->getParentTheme();
        }

        return array_reverse($hierarchy);
    }
}
