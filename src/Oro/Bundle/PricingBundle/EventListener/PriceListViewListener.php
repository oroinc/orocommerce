<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\PricingBundle\Async\PriceListCalculationNotificationAlert;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks for existing price list rules and assignments calculation alerts and notifies user
 * on appropriate price list view pages
 */
class PriceListViewListener
{
    private NotificationAlertManager $notificationAlertManager;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;

    public function __construct(
        NotificationAlertManager $notificationAlertManager,
        RequestStack $requestStack,
        TranslatorInterface $translator
    ) {
        $this->notificationAlertManager = $notificationAlertManager;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    public function onPriceListView(BeforeListRenderEvent $event)
    {
        if (!$event->getEntity() instanceof PriceList) {
            return;
        }
        /** @var PriceList $priceList */
        $priceList = $event->getEntity();

        if (!$priceList->isActual()) {
            $this->requestStack?->getSession()?->getFlashBag()->add(
                'warning',
                $this->translator->trans('oro.pricing.pricelist.not_actual.recalculation')
            );
        }

        $hasPriceRuleBuildAlert = $this->notificationAlertManager->hasNotificationAlertsByOperationAndItemId(
            PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
            $priceList->getId()
        );
        if ($hasPriceRuleBuildAlert) {
            $this->requestStack?->getSession()?->getFlashBag()->add(
                'error',
                $this->translator->trans('oro.pricing.notification.price_list.error.price_rule_build')
            );
        }

        $hasAssignedProductsBuildAlert = $this->notificationAlertManager->hasNotificationAlertsByOperationAndItemId(
            PriceListCalculationNotificationAlert::OPERATION_ASSIGNED_PRODUCTS_BUILD,
            $priceList->getId()
        );
        if ($hasAssignedProductsBuildAlert) {
            $this->requestStack?->getSession()?->getFlashBag()->add(
                'error',
                $this->translator->trans('oro.pricing.notification.price_list.error.product_assignment_build')
            );
        }
    }
}
