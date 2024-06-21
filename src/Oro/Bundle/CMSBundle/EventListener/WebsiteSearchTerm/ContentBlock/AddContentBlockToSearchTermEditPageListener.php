<?php

namespace Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\ContentBlock;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * Adds content block field to a search term edit page in backoffice.
 */
class AddContentBlockToSearchTermEditPageListener
{
    public function onEntityEdit(BeforeListRenderEvent $event): void
    {
        $scrollData = $event->getScrollData();
        $scrollDataData = $scrollData->getData();
        if (!isset($scrollDataData[ScrollData::DATA_BLOCKS]['action'])) {
            return;
        }

        $twig = $event->getEnvironment();

        $actionBlock = &$scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['contentBlock'] = $twig->render(
            '@OroCMS/SearchTerm/content_block_form.html.twig',
            ['form' => $event->getFormView()]
        );

        $scrollData->setData($scrollDataData);
    }
}
