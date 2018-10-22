<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for AjaxProductPriceControllers (backend and frontend)
 * Is used to handle common logic for ProductPrice related actions
 * see method descriptions for more details
 */
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
                    $this->getRequestProducts($request),
                    $request->get('currency')
                )
        );
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getRequestProducts(Request $request): array
    {
        $productIds = $request->get('product_ids', []);
        $doctrineHelper = $this->container->get('oro_entity.doctrine_helper');
        return array_map(
            function ($productId) use ($doctrineHelper) {
                return $doctrineHelper->getEntityReference(Product::class, $productId);
            },
            array_filter($productIds)
        );
    }
}
