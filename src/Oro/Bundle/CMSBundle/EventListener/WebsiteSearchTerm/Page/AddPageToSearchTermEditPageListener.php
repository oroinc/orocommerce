<?php

namespace Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\Page;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * Adds page field to a search term edit page in backoffice.
 */
class AddPageToSearchTermEditPageListener
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
                $actionSubBlockData['redirectCmsPage'] = $twig->render(
                    '@OroCMS/SearchTerm/redirect_cms_page_form.html.twig',
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
