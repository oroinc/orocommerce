<?php

namespace Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\ContentBlock;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Adds content block name to search term view page in backoffice.
 */
class AddContentBlockToSearchTermViewPageListener
{
    public function onEntityView(BeforeListRenderEvent $event): void
    {
        /** @var SearchTerm $searchTerm */
        $searchTerm = $event->getEntity();
        if ($searchTerm->getActionType() !== 'modify' || !$searchTerm->getContentBlock()) {
            return;
        }

        $scrollData = $event->getScrollData();
        $twig = $event->getEnvironment();
        $scrollDataData = $scrollData->getData();
        $actionBlock = & $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['contentBlock'] = $twig->render(
            '@OroCMS/SearchTerm/content_block_field.html.twig',
            ['entity' => $searchTerm->getContentBlock()]
        );
        $scrollData->setData($scrollDataData);
    }
}
