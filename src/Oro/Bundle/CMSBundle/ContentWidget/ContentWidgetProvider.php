<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;

/**
 * Provides a method to get a content widget by its name.
 */
class ContentWidgetProvider
{
    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getContentWidget(string $widgetName): ContentWidget
    {
        $widget = $this->doctrine->getRepository(ContentWidget::class)
            ->findOneBy(['name' => $widgetName]);
        if (null === $widget) {
            throw new \RuntimeException(sprintf(
                'The context widget "%s" does not exist.',
                $widgetName
            ));
        }

        return $widget;
    }
}
