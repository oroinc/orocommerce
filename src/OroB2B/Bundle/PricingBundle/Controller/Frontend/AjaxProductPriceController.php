<?php

namespace OroB2B\Bundle\PricingBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Controller\AbstractAjaxProductPriceController;

class AjaxProductPriceController extends AbstractAjaxProductPriceController
{
    /**
     * @Route("/get-product-prices-by-pricelist", name="orob2b_pricing_frontend_price_by_pricelist")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductPricesByPriceListAction(Request $request)
    {
        return parent::getProductPricesByPriceListAction($request);
    }

    /**
     * @Route("/get-matching-price", name="orob2b_pricing_frontend_matching_price")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getMatchingPriceAction(Request $request)
    {
        $lineItems = $request->get('items', []);

        $productUnitQuantities = $this->prepareProductUnitQuantities($lineItems);

        /** @var Price[] $matchedPrice */
        $matchedPrice = $this->get('orob2b_pricing.provider.product_price')
            ->getMatchedPrices($productUnitQuantities);

        return new JsonResponse($this->formatMatchedPrices($matchedPrice));
    }

    /**
     * @Route("/get-product-units-by-currency", name="orob2b_pricing_frontend_units_by_pricelist")
     * @Method({"GET"})
     *
     * {@inheritdoc}
     */
    public function getProductUnitsByCurrencyAction(Request $request)
    {
        return $this->getProductUnitsByCurrency(
            $this->get('orob2b_pricing.model.frontend.price_list_request_handler')->getPriceList(),
            $request
        );
    }
}
