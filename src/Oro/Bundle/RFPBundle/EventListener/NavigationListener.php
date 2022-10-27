<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Show/Hide menu item
 */
class NavigationListener
{
    const MENU_ITEM_ID = 'oro_rfp_frontend_request_index';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var FeatureChecker */
    private $featureChecker;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        FeatureChecker $featureChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->featureChecker = $featureChecker;
    }

    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $rfpItem = MenuUpdateUtils::findMenuItem($event->getMenu(), self::MENU_ITEM_ID);
        if ($rfpItem !== null) {
            $isDisplay = false;
            if ($this->authorizationChecker->isGranted('oro_rfp_frontend_request_view') ||
                $this->featureChecker->isResourceEnabled('oro_rfp_frontend_request_index', 'routes')) {
                $isDisplay = true;
            }
            $rfpItem->setDisplay($isDisplay);
        }
    }
}
