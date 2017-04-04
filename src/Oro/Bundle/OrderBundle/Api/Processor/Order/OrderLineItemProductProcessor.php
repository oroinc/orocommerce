<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\Order;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\SkuCachedProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class OrderLineItemProductProcessor implements ProcessorInterface
{
    /**
     * @var SkuCachedProductProvider
     */
    private $skuCachedProductProvider;

    /**
     * @param SkuCachedProductProvider $skuCachedProductProvider
     */
    public function __construct(SkuCachedProductProvider $skuCachedProductProvider)
    {
        $this->skuCachedProductProvider = $skuCachedProductProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return;
        }

        $requestData = $context->getRequestData();
        $orderLineItem = $context->getResult();

        if (!$requestData || !$orderLineItem instanceof OrderLineItem) {
            return;
        }

        if (array_key_exists('freeFormProduct', $requestData)) {
            return;
        }

        if (array_key_exists('product', $requestData)) {
            return;
        }

        if (false === array_key_exists('productSku', $requestData)) {
            return;
        }

        $product = $this->skuCachedProductProvider->getBySku($requestData['productSku']);

        if (null === $product) {
            return;
        }

        $requestData['product'] = [
            'class' => Product::class,
            'id' => $product->getId(),
        ];

        $context->setRequestData($requestData);
    }
}
