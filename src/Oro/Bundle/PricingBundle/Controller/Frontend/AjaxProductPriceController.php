<?php

namespace Oro\Bundle\PricingBundle\Controller\Frontend;

use Oro\Bundle\PricingBundle\Controller\AbstractAjaxProductPriceController;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds actions to get prices by customer or matching prices via AJAX
 */
class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @Route("/get-product-prices-by-customer", name="oro_pricing_frontend_price_by_customer")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductPricesByCustomerAction(Request $request)
    {
        return parent::getProductPricesByCustomer($request);
    }

    /**
     * @Route("/get-matching-price", name="oro_pricing_frontend_matching_price")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);
        $matchedPrices = $this->get('oro_pricing.provider.matching_price')->getMatchingPrices(
            $lineItems,
            $this->get('oro_pricing.model.product_price_scope_criteria_request_handler')->getPriceScopeCriteria()
        );

        return new JsonResponse($matchedPrices);
    }

    /**
     * @Route("/set-current-currency", name="oro_pricing_frontend_set_current_currency")
     * @Method({"POST"})
     * @CsrfProtection()
     *
     * {@inheritdoc}
     */
    public function setCurrentCurrencyAction(Request $request)
    {
        $currency = $request->get('currency');
        $result = false;
        $userCurrencyManager = $this->get('oro_pricing.user_currency_manager');
        if (in_array($currency, $userCurrencyManager->getAvailableCurrencies(), true)) {
            $userCurrencyManager->saveSelectedCurrency($currency);
            $result = true;
        }

        return new JsonResponse(['success' => $result]);
    }
}
