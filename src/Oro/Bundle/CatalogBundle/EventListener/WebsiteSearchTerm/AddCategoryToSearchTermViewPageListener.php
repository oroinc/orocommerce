<?php

namespace Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Adds category redirect action type to a search term view page in backoffice.
 */
class AddCategoryToSearchTermViewPageListener
{
    public function onEntityView(BeforeListRenderEvent $event): void
    {
        /** @var SearchTerm $searchTerm */
        $searchTerm = $event->getEntity();
        if ($searchTerm->getActionType() !== 'redirect' || $searchTerm->getRedirectActionType() !== 'category') {
            return;
        }

        $scrollData = $event->getScrollData();
        $twig = $event->getEnvironment();
        $scrollDataData = $scrollData->getData();
        $actionBlock =& $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['redirectCategory'] = $twig->render(
            '@OroCatalog/SearchTerm/redirect_category_field.html.twig',
            ['entity' => $searchTerm->getRedirectCategory()]
        );
        $scrollData->setData($scrollDataData);
    }
}
