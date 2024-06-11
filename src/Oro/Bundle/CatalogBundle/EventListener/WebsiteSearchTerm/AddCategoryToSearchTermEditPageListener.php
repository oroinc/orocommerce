<?php

namespace Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * Adds category field to a search term edit page in backoffice.
 */
class AddCategoryToSearchTermEditPageListener
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
                $actionSubBlockData['redirectCategory'] = $twig->render(
                    '@OroCatalog/SearchTerm/redirect_category_form.html.twig',
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
