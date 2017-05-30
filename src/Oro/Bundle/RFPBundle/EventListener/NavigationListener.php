<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Show/Hide menu item
 */
class NavigationListener
{
    const MENU_ITEM_ID = 'oro_rfp_frontend_request_index';

    /** @var SecurityFacade */
    private $securityFacade;

    /** @var FeatureChecker */
    private $featureChecker;

    /**
     * @param SecurityFacade $securityFacade
     * @param FeatureChecker $featureChecker
     */
    public function __construct(SecurityFacade $securityFacade, FeatureChecker $featureChecker)
    {
        $this->securityFacade = $securityFacade;
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $rfpItem = MenuUpdateUtils::findMenuItem($event->getMenu(), self::MENU_ITEM_ID);
        if ($rfpItem !== null) {
            $isDisplay = false;
            if ($this->securityFacade->isGranted('oro_rfp_frontend_request_view') ||
                $this->featureChecker->isResourceEnabled('oro_rfp_frontend_request_index', 'routes')) {
                $isDisplay = true;
            }
            $rfpItem->setDisplay($isDisplay);
        }
    }
}
