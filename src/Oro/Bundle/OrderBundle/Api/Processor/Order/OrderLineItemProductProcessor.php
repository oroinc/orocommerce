<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\Order;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class OrderLineItemProductProcessor implements ProcessorInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
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

        $product = $this->doctrineHelper->getEntityRepository(Product::class)
            ->findOneBy(['sku' => $requestData['productSku']]);

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
