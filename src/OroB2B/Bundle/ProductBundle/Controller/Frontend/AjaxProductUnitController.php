<?php

namespace OroB2B\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class AjaxProductUnitController extends Controller
{
    /**
     * @Route("/product-units/{id}", name="orob2b_product_frontend_unit_product_units", requirements={"id"="\d+"})
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function getProductUnitsAction(Product $product)
    {
        $class = $this->container->getParameter('orob2b_product.product_unit.class');
        /** @var ProductUnitRepository $repository */
        $repository = $this->getDoctrine()->getManagerForClass($class)->getRepository($class);
        /** @var ProductUnitLabelFormatter $formatter */
        $formatter =  $this->container->get('orob2b_product.formatter.product_unit_label');

        return new JsonResponse(
            [
                'units' => $formatter->formatChoices($repository->getProductUnits($product)),
            ]
        );
    }
}
