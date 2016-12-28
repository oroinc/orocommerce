<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $productFormDataProvider = $this->get('oro_product.layout.data_provider.product_form');
        $productVariantForm = $productFormDataProvider->getVariantFieldsForm($configurableProduct);

        $productVariantAvailabilityProvider = $this->get('oro_product.provider.product_variant_availability_provider');

        /** @var array $variantFields */
        $variantFields = $request->get($productVariantForm->getName(), []);

        $fieldsToSearch = [];
        foreach ($variantFields as $name => $value) {
            if ($productVariantForm->has($name)) {
                $fieldsToSearch[$name] = $value;
            }
        }

        try {
            return $productVariantAvailabilityProvider
                ->getSimpleProductByVariantFields($configurableProduct, $fieldsToSearch);
        } catch (\InvalidArgumentException $e) {
            // Can't find one product by parameters
        }

        return null;
    }
}
