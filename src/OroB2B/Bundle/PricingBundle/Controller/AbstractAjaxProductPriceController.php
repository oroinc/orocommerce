<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AbstractAjaxProductPriceController extends Controller
{
    /**
     * Get products prices by price list and product ids
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductPricesByPriceListAction(Request $request)
    {
        $params = $request->query->all();

        $priceListId = isset($params['price_list_id']) ? $params['price_list_id'] : null;
        $productIds = isset($params['product_ids']) ? $params['product_ids'] : null;
        $currency = isset($params['currency']) ? $params['currency'] : null;

        $currencies = $this->get('orob2b_pricing.provider.product_price')->getPriceByPriceListIdAndProductIds(
            $priceListId,
            $productIds,
            $currency
        );

        return new JsonResponse($currencies);
    }
}
