<?php

namespace Oro\Bundle\PricingBundle\Controller\Frontend;

use Oro\Bundle\PricingBundle\Controller\AbstractAjaxProductPriceController;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Adds actions to get prices by customer or matching prices via AJAX
 */
class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @Route("/get-product-prices-by-customer", name="oro_pricing_frontend_price_by_customer", methods={"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductPricesByCustomerAction(Request $request)
    {
        return parent::getProductPricesByCustomer($request);
    }

    /**
     * @Route("/get-matching-price", name="oro_pricing_frontend_matching_price", methods={"GET"})
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);
        $matchedPrices = $this->get(MatchingPriceProvider::class)->getMatchingPrices(
            $lineItems,
            $this->get(ProductPriceScopeCriteriaRequestHandler::class)->getPriceScopeCriteria()
        );

        return new JsonResponse($matchedPrices);
    }

    /**
     * @Route("/set-current-currency", name="oro_pricing_frontend_set_current_currency", methods={"POST"})
     * @CsrfProtection()
     *
     * {@inheritdoc}
     */
    public function setCurrentCurrencyAction(Request $request)
    {
        $currency = $request->get('currency');
        $result = false;
        $userCurrencyManager = $this->get(UserCurrencyManager::class);
        if (in_array($currency, $userCurrencyManager->getAvailableCurrencies(), true)) {
            $userCurrencyManager->saveSelectedCurrency($currency);
            $result = true;
        }

        return new JsonResponse(['success' => $result]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                MatchingPriceProvider::class,
                UserCurrencyManager::class,
            ]
        );
    }
}
