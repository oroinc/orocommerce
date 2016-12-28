<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

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
        $productFormDataProvider = $this->get('oro_product.layout.data_provider.product_form');
        $productVariantForm = $productFormDataProvider->getVariantFieldsForm($product);

        $content = $this->get('oro_layout.layout_manager')->render(
            [
                'data' => ['product' => $product],
                'form' => $productVariantForm,
                'action' => 'oro_product_frontend_product_variants',
                'widget_container' => 'ajax'
            ],
            ['form']
        );

        return new JsonResponse([
            'data' => [
                'form' => $content,
            ]
        ]);
    }
}
