<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * Adds content node field to a search term edit page in backoffice.
 */
class AddContentNodeToSearchTermEditPageListener
{
    public function onEntityEdit(BeforeListRenderEvent $event): void
    {
        $scrollData = $event->getScrollData();
        $scrollDataData = $scrollData->getData();
        if (!isset($scrollDataData[ScrollData::DATA_BLOCKS]['action'])) {
            return;
        }

        $twig = $event->getEnvironment();
        $actionBlock = & $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionSubBlockData = [];

        foreach ($actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA] ?? [] as $fieldName => $fieldValue) {
            if ($fieldName === 'redirect301') {
                $actionSubBlockData['redirectContentNode'] = $twig->render(
                    '@OroWebCatalog/SearchTerm/redirect_content_node_form.html.twig',
                    ['form' => $event->getFormView()]
                );
            }

            $actionSubBlockData[$fieldName] = $fieldValue;
        }

        if ($actionSubBlockData) {
            $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA] = $actionSubBlockData;

            $scrollData->setData($scrollDataData);
        }
    }
}
