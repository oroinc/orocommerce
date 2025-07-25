<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\ProductBundle\Controller\AbstractAjaxProductUnitController;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Use this class for getting product units on frontend.
 */
class AjaxProductUnitController extends AbstractAjaxProductUnitController
{
    /**
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    #[Route(
        path: '/product-units/{id}',
        name: 'oro_product_frontend_ajaxproductunit_productunits',
        requirements: ['id' => '\d+']
    )]
    public function productUnitsAction(Request $request, Product $product)
    {
        return $this->getProductUnits($product);
    }
}
