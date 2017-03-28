<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\Order;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\SkuCachedProductProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class OrderProductCacherProcessor implements ProcessorInterface
{
    const ORDER_LINE_ITEM_API_TYPE = 'orderlineitems';

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

        $order = $context->getResult();

        if (!$order instanceof Order) {
            return;
        }

        $includedData = $context->getIncludedData();

        if (null === $includedData || [] === $includedData) {
            return;
        }

        foreach ($includedData as $includedItem) {
            $this->handleOrderLineItem($includedItem);
        }
    }

    /**
     * @param array $includedItem
     */
    private function handleOrderLineItem(array $includedItem)
    {
        if (false === array_key_exists('data', $includedItem)) {
            return;
        }

        $data = $includedItem['data'];

        if (false === array_key_exists('type', $data)) {
            return;
        }

        if ($data['type'] !== self::ORDER_LINE_ITEM_API_TYPE) {
            return;
        }

        if (false === array_key_exists('attributes', $data)) {
            return;
        }

        $dataAttributes = $data['attributes'];

        if (false === array_key_exists('productSku', $dataAttributes)) {
            return;
        }

        if (array_key_exists('freeFormProduct', $dataAttributes)) {
            return;
        }

        if (array_key_exists('relationships', $data)
            && array_key_exists('product', $data['relationships'])
        ) {
            return;
        }

        $this->skuCachedProductProvider->addSkuToCache($dataAttributes['productSku']);
    }
}
