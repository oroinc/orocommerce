<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * Adds product collection field to a search term edit page in backoffice.
 */
class AddProductCollectionToSearchTermEditPageListener
{
    public function onEntityEdit(BeforeListRenderEvent $event): void
    {
        $scrollData = $event->getScrollData();
        $scrollDataData = $scrollData->getData();
        if (!isset($scrollDataData[ScrollData::DATA_BLOCKS]['action'])) {
            return;
        }

        $twig = $event->getEnvironment();
        $actionBlock =& $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionSubBlockData = [];

        foreach ($actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA] ?? [] as $fieldName => $fieldValue
        ) {
            $actionSubBlockData[$fieldName] = $fieldValue;

            if ($fieldName === 'modifyActionType') {
                $actionSubBlockData['productCollectionSegment'] = $twig->render(
                    '@OroProduct/SearchTerm/product_collection_segment_form.html.twig',
                    ['form' => $event->getFormView()]
                );
            }
        }

        if ($actionSubBlockData) {
            $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA] =
                $actionSubBlockData;

            $scrollData->setData($scrollDataData);
        }
    }
}
