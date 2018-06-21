<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAjaxProductPriceController extends Controller
{
    /**
     * Get products prices by price list and product ids
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductPricesByCustomer(Request $request)
    {
        $scopeCriteria = $this->get('oro_pricing.model.product_price_scope_criteria_request_handler')
            ->getPriceScopeCriteria();

        return new JsonResponse(
            $this->get('oro_pricing.provider.product_price')
                ->getPricesByScopeCriteriaAndProductIds(
                    $scopeCriteria,
                    $request->get('product_ids', []),
                    $request->get('currency')
                )
        );
    }
}
