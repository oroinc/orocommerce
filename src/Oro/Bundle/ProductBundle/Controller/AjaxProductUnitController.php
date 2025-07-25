<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Use this class for getting product units on admin panel.
 */
class AjaxProductUnitController extends AbstractAjaxProductUnitController
{
    /**
     * @return JsonResponse
     */
    #[Route(path: '/product-units', name: 'oro_product_unit_all_product_units')]
    #[AclAncestor('oro_product_view')]
    public function getAllProductUnitsAction()
    {
        return $this->getAllProductUnits();
    }

    /**
     *
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    #[Route(path: '/product-units/{id}', name: 'oro_product_unit_product_units', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_product_view')]
    public function getProductUnitsAction(Request $request, Product $product)
    {
        return $this->getProductUnits($product);
    }
}
