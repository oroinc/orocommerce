<?php

namespace OroB2B\Bundle\ProductBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

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
     * @return JsonResponse
     */
    protected function getProductUnits(Product $product)
    {
        return new JsonResponse(
            [
                'units' => $this->getProductUnitFormatter()
                    ->formatChoices($this->getRepository()->getProductUnits($product))
            ]
        );
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        $class = $this->container->getParameter('orob2b_product.product_unit.class');

        return $this->getDoctrine()->getManagerForClass($class)->getRepository($class);
    }

    /**
     * @return ProductUnitLabelFormatter
     */
    protected function getProductUnitFormatter()
    {
        return $this->container->get('orob2b_product.formatter.product_unit_label');
    }
}
