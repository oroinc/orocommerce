<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Factory\PaymentLineItemBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;

/**
 * Converts order line items to a collection of payment line items.
 */
class BasicOrderPaymentLineItemConverter implements OrderPaymentLineItemConverterInterface
{
    private PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory;
    private PaymentLineItemBuilderFactoryInterface $paymentLineItemBuilderFactory;

    public function __construct(
        PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory,
        PaymentLineItemBuilderFactoryInterface $paymentLineItemBuilderFactory
    ) {
        $this->paymentLineItemCollectionFactory = $paymentLineItemCollectionFactory;
        $this->paymentLineItemBuilderFactory = $paymentLineItemBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function convertLineItems(Collection $orderLineItems): PaymentLineItemCollectionInterface
    {
        $paymentLineItems = [];
        foreach ($orderLineItems as $orderLineItem) {
            if ($orderLineItem->getProductUnit() === null) {
                $paymentLineItems = [];
                break;
            }

            $builder = $this->paymentLineItemBuilderFactory->createBuilder(
                $orderLineItem->getProductUnit(),
                $orderLineItem->getProductUnit()->getCode(),
                $orderLineItem->getQuantity(),
                $orderLineItem
            );
            if (null !== $orderLineItem->getProduct()) {
                $builder->setProduct($orderLineItem->getProduct());
            }
            if (null !== $orderLineItem->getPrice()) {
                $builder->setPrice($orderLineItem->getPrice());
            }
            $paymentLineItems[] = $builder->getResult();
        }

        return $this->paymentLineItemCollectionFactory->createPaymentLineItemCollection($paymentLineItems);
    }
}
