<?php

namespace OroB2B\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class AjaxProductUnitController extends AbstractAjaxProductUnitController
{
    /**
     * @Route("/product-units", name="orob2b_product_unit_all_product_units")
     * @AclAncestor("orob2b_product_view")
     *
     * @return JsonResponse
     */
    public function getAllProductUnitsAction()
    {
        return $this->getAllProductUnits();
    }

    /**
     * @Route("/product-units/{id}", name="orob2b_product_unit_product_units", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_product_view")
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function getProductUnitsAction(Product $product)
    {
        return $this->getProductUnits($product);
    }
}
