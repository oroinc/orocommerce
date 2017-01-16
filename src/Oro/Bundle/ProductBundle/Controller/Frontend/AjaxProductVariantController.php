<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType;

class AjaxProductVariantController extends Controller
{
    /**
     * @Route(
     *      "/available-variants/{id}",
     *      name="oro_product_frontend_ajax_product_variant_get_available",
     *      requirements={"id"="\d+"}
     * )
     *
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function getAvailableAction(Request $request, Product $product)
    {
        $response = [
            'data' => []
        ];

        $product = $this->getAvailableProduct($request, $product);

        if ($product instanceof Product) {
            $response['data']['id'] = $product->getId();
        }

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @param Product $configurableProduct
     * @return null|Product
     */
    private function getAvailableProduct(Request $request, Product $configurableProduct)
    {
        $options = [
            'parentProduct' => $configurableProduct
        ];

        $form = $this->createForm(FrontendVariantFiledType::NAME, new Product(), $options);
        $form->handleRequest($request);

        if ($form->isValid()) {
            return $form->getData();
        }

        return null;
    }
}
