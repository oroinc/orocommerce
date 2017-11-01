<?php

namespace Oro\Bundle\RFPBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\RFPBundle\Entity\Request;

class RequestProductsExtension extends \Twig_Extension
{
    const NAME = 'oro_rfp_request_products';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [new \Twig_SimpleFunction('rfp_products', [$this, 'getRequestProducts'])];
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
                $items[$productItem->getId()] = [
                    'quantity' => $productItem->getQuantity(),
                    'price' => $productItem->getPrice(),
                    'unit' => $productItem->getProductUnitCode(),
                ];
            }

            $data['items'] = $items;

            $result[$product->getId()] = $data;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
