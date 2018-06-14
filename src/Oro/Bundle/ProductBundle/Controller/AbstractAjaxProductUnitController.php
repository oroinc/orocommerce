<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Abstract class used for getting product units on frontend
 */
abstract class AbstractAjaxProductUnitController extends Controller
{
    /**
     * @return JsonResponse
     */
    protected function getAllProductUnits()
    {
        return new JsonResponse(
            [
                'units' => $this->getProductUnitFormatter()
                    ->formatChoices($this->getRepository()->findBy([], ['code' => 'ASC']))
            ]
        );
    }

    /**
     * @param Product $product
     *
     * @return JsonResponse
     */
    protected function getProductUnits(Product $product)
    {
        return new JsonResponse(
            [
                'units' => $product->getSellUnitsPrecision(),
            ]
        );
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        $class = $this->container->getParameter('oro_product.entity.product_unit.class');

        return $this->getDoctrine()->getManagerForClass($class)->getRepository($class);
    }

    /**
     * @return ProductUnitLabelFormatter
     */
    protected function getProductUnitFormatter()
    {
        return $this->container->get('oro_product.formatter.product_unit_label');
    }
}
