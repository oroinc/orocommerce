<?php

namespace Oro\Bundle\PricingBundle\Controller\Frontend;

use Oro\Bundle\PricingBundle\Controller\AbstractAjaxProductPriceController;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Adds actions to get prices by customer or matching prices via AJAX
 */
class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    #[Route(path: '/get-product-prices-by-customer', name: 'oro_pricing_frontend_price_by_customer', methods: ['GET'])]
    public function getProductPricesByCustomerAction(Request $request)
    {
        return parent::getProductPricesByCustomer($request);
    }

    #[Route(path: '/set-current-currency', name: 'oro_pricing_frontend_set_current_currency', methods: ['POST'])]
    #[CsrfProtection()]
    public function setCurrentCurrencyAction(Request $request)
    {
        $currency = $request->get('currency');
        $result = false;
        $userCurrencyManager = $this->container->get(UserCurrencyManager::class);
        if (in_array($currency, $userCurrencyManager->getAvailableCurrencies(), true)) {
            $userCurrencyManager->saveSelectedCurrency($currency);
            $result = true;
        }

        return new JsonResponse(['success' => $result]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UserCurrencyManager::class,
            ]
        );
    }
}
