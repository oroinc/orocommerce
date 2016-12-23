<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Factory\PaymentLineItemBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;

class BasicOrderPaymentLineItemConverter implements OrderPaymentLineItemConverterInterface
{
    /**
     * @var PaymentLineItemCollectionFactoryInterface|null
     */
    private $paymentLineItemCollectionFactory = null;

    /**
     * @var PaymentLineItemBuilderFactoryInterface|null
     */
    private $paymentLineItemBuilderFactory = null;

    /**
     * @param null|PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory
     * @param null|PaymentLineItemBuilderFactoryInterface $paymentLineItemBuilderFactory
     */
    public function __construct(
        PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory = null,
        PaymentLineItemBuilderFactoryInterface $paymentLineItemBuilderFactory = null
    ) {
        $this->paymentLineItemCollectionFactory = $paymentLineItemCollectionFactory;
        $this->paymentLineItemBuilderFactory = $paymentLineItemBuilderFactory;
    }

    /**
     * @param OrderLineItem[]|Collection $orderLineItems
     * {@inheritDoc}
     */
    public function convertLineItems(Collection $orderLineItems)
    {
        if (null === $this->paymentLineItemCollectionFactory || null === $this->paymentLineItemBuilderFactory) {
            return null;
        }

        $paymentLineItems = [];
        foreach ($orderLineItems as $orderLineItem) {
            $builder = $this->paymentLineItemBuilderFactory->createBuilder(
                $orderLineItem->getPrice(),
                $orderLineItem->getProductUnit(),
                $orderLineItem->getProductUnit()->getCode(),
                $orderLineItem->getQuantity(),
                $orderLineItem
            );

            if (null !== $orderLineItem->getProduct()) {
                $builder->setProduct($orderLineItem->getProduct());
            }

            $paymentLineItems[] = $builder->getResult();
        }

        return $this->paymentLineItemCollectionFactory->createPaymentLineItemCollection($paymentLineItems);
    }
}
