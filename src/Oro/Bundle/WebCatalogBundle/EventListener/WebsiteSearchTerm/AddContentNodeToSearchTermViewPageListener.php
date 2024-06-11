<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Adds content node redirect action type to a search term view page in backoffice.
 */
class AddContentNodeToSearchTermViewPageListener
{
    public function onEntityView(BeforeListRenderEvent $event): void
    {
        /** @var SearchTerm $searchTerm */
        $searchTerm = $event->getEntity();
        if ($searchTerm->getActionType() !== 'redirect' || $searchTerm->getRedirectActionType() !== 'content_node') {
            return;
        }

        $scrollData = $event->getScrollData();
        $twig = $event->getEnvironment();
        $scrollDataData = $scrollData->getData();
        $actionBlock = & $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['redirectContentNode'] = $twig->render(
            '@OroWebCatalog/SearchTerm/redirect_content_node_field.html.twig',
            ['entity' => $searchTerm->getRedirectContentNode()]
        );
        $scrollData->setData($scrollDataData);
    }
}
