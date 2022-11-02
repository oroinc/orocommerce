<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\FrontendVariantFiledType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Serves AJAX Product variants actions.
 */
class AjaxProductVariantController extends AbstractController
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

        $form = $this->createForm(FrontendVariantFiledType::class, new Product(), $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $form->getData();
        }

        return null;
    }
}
