<?php

namespace Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\Page;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Adds page to a search term view page in backoffice.
 */
class AddPageToSearchTermViewPageListener
{
    public function onEntityView(BeforeListRenderEvent $event): void
    {
        /** @var SearchTerm $searchTerm */
        $searchTerm = $event->getEntity();
        if ($searchTerm->getActionType() !== 'redirect' || $searchTerm->getRedirectActionType() !== 'cms_page') {
            return;
        }

        $scrollData = $event->getScrollData();
        $twig = $event->getEnvironment();
        $scrollDataData = $scrollData->getData();
        $actionBlock = & $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['redirectCmsPage'] = $twig->render(
            '@OroCMS/SearchTerm/redirect_cms_page_field.html.twig',
            ['entity' => $searchTerm->getRedirectCmsPage()]
        );
        $scrollData->setData($scrollDataData);
    }
}
