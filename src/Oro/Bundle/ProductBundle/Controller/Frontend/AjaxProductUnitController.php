<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ProductBundle\Controller\AbstractAjaxProductUnitController;
use Oro\Bundle\ProductBundle\Entity\Product;

class AjaxProductUnitController extends AbstractAjaxProductUnitController
{
    /**
     * @Route(
     *      "/product-units/{id}",
     *      name="oro_product_frontend_ajaxproductunit_productunits",
     *      requirements={"id"="\d+"}
     * )
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function productUnitsAction(Request $request, Product $product)
    {
        $isShort = (bool)$request->get('short', false);

        return $this->getProductUnits($product, $isShort);
    }
}
