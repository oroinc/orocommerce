<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\Product;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Adds product redirect action type to a search term view page in backoffice.
 */
class AddProductToSearchTermViewPageListener
{
    public function onEntityView(BeforeListRenderEvent $event): void
    {
        /** @var SearchTerm $searchTerm */
        $searchTerm = $event->getEntity();
        if ($searchTerm->getActionType() !== 'redirect' || $searchTerm->getRedirectActionType() !== 'product') {
            return;
        }

        $scrollData = $event->getScrollData();
        $twig = $event->getEnvironment();
        $scrollDataData = $scrollData->getData();
        $actionBlock =& $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['redirectProduct'] = $twig->render(
            '@OroProduct/SearchTerm/redirect_product_field.html.twig',
            ['entity' => $searchTerm->getRedirectProduct()]
        );
        $scrollData->setData($scrollDataData);
    }
}
