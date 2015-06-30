<?php

namespace OroB2B\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class AjaxProductUnitController extends Controller
{
    /**
     * @Route("/product-units/{id}", name="orob2b_product_unit_product_units", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_product_view")
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function getProductUnitsAction(Product $product)
    {
        $units = $this->getRepository()->getProductUnits($product);
        $result = [];

        /* @var $formatter ProductUnitLabelFormatter */
        $formatter = $this->container->get('orob2b_product.formatter.product_unit_label');

        foreach ($units as $unit) {
            $result[$unit->getCode()] = $formatter->format($unit->getCode());
        }

        return new JsonResponse(['units' => $result]);
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        $class = $this->container->getParameter('orob2b_product.product_unit.class');

        return $this->getDoctrine()->getManagerForClass($class)->getRepository($class);
    }
}
