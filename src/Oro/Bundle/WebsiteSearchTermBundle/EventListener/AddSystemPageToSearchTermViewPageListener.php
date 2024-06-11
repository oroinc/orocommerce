<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\EventListener;

use Oro\Bundle\NavigationBundle\Provider\RouteTitleProvider;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Adds system page redirect action type to a search term view page in backoffice.
 */
class AddSystemPageToSearchTermViewPageListener
{
    public function __construct(private readonly RouteTitleProvider $routeTitleProvider)
    {
    }

    public function onEntityView(BeforeListRenderEvent $event): void
    {
        /** @var SearchTerm $searchTerm */
        $searchTerm = $event->getEntity();
        if ($searchTerm->getActionType() !== 'redirect' || $searchTerm->getRedirectActionType() !== 'system_page') {
            return;
        }

        $scrollData = $event->getScrollData();
        $twig = $event->getEnvironment();
        $scrollDataData = $scrollData->getData();
        $actionBlock = & $scrollDataData[ScrollData::DATA_BLOCKS]['action'];
        $actionBlock[ScrollData::SUB_BLOCKS][0][ScrollData::DATA]['redirectSystemPage'] = $twig->render(
            '@OroWebsiteSearchTerm/SearchTerm/redirect_system_page_field.html.twig',
            [
                'entity' => $searchTerm,
                'systemPageTitle' => $this->routeTitleProvider
                    ->getTitle($searchTerm->getRedirectSystemPage(), 'frontend_menu'),
            ]
        );
        $scrollData->setData($scrollDataData);
    }
}
