<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ProductBundle\Entity\Product;

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
     * @return JsonResponse
     */
    public function getAvailableAction(Request $request, Product $product)
    {
        $productFormDataProvider = $this->get('oro_product.layout.data_provider.product_form');
        $productVariantForm = $productFormDataProvider->getVariantFieldsForm($product);

        $productVariantForm->handleRequest($request);

        return [
            'fields' => $productVariantForm->createView()
        ];
    }
}
