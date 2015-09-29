<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ProductBundle\Controller\AbstractAjaxProductUnitController;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class AjaxProductUnitController extends AbstractAjaxProductUnitController
{
    /**
     * @Route(
     *      "/product-units/{id}",
     *      name="orob2b_product_frontend_ajaxproductunit_productunits",
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
