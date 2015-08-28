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
     * @Route("/product-units", name="orob2b_product_unit_all_product_units")
     * @AclAncestor("orob2b_product_view")
     *
     * @return JsonResponse
     */
    public function getAllProductUnitsAction()
    {
        return new JsonResponse(
            [
                'units' => $this->getProductUnitFormatter()
                    ->formatChoices($this->getRepository()->findBy([], ['code' => 'ASC']))
            ]
        );
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
