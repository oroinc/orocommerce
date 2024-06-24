<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Adds product collection action type to a search term view page in backoffice.
 */
class AddProductCollectionToSearchTermViewPageListener
{
    public function onEntityView(BeforeListRenderEvent $event): void
    {
        /** @var SearchTerm $searchTerm */
        $searchTerm = $event->getEntity();
        if ($searchTerm->getActionType() !== 'modify'
            || $searchTerm->getModifyActionType() !== 'product_collection') {
            return;
        }

        $scrollData = $event->getScrollData();
        $twig = $event->getEnvironment();
        $scrollDataData = $scrollData->getData();
        $actionBlock = & $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['productCollection'] = $twig->render(
            '@OroProduct/SearchTerm/product_collection_field.html.twig',
            ['entity' => $searchTerm->getProductCollectionSegment()]
        );
        $scrollData->setData($scrollDataData);
    }
}
