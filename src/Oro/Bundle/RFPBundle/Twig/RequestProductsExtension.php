<?php

namespace Oro\Bundle\RFPBundle\Twig;

use Oro\Bundle\RFPBundle\Entity\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve products from a request for quote:
 *   - rfp_products
 */
class RequestProductsExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [new TwigFunction('rfp_products', [$this, 'getRequestProducts'])];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getRequestProducts(Request $request)
    {
        $result = [];
        foreach ($request->getRequestProducts() as $requestProduct) {
            $product = $requestProduct->getProduct();
            $data['name'] = (string)$product;
            $data['sku'] = $requestProduct->getProductSku();
            $data['comment'] = $requestProduct->getComment();

            $items = [];
            foreach ($requestProduct->getRequestProductItems() as $productItem) {
                $items[] = [
                    'quantity' => $productItem->getQuantity(),
                    'price' => $productItem->getPrice(),
                    'unit' => $productItem->getProductUnitCode(),
                ];
            }

            $data['items'] = $items;

            $result[] = $data;
        }

        return $result;
    }
}
