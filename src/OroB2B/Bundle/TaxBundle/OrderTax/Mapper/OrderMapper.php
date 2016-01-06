<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class OrderMapper implements TaxMapperInterface
{
    const PROCESSING_CLASS_NAME = 'OroB2B\Bundle\OrderBundle\Entity\Order';

    /**
     * @var OrderLineItemMapper
     */
    protected $orderLineItemMapper;

    /**
     * @param OrderLineItemMapper $orderLineItemMapper
     */
    public function __construct(OrderLineItemMapper $orderLineItemMapper)
    {
        $this->orderLineItemMapper = $orderLineItemMapper;
    }

    /**
     * {@inheritdoc}
     * @param Order $order
     */
    public function map($order)
    {
        $taxable = (new Taxable())
            ->setAmount($order->getSubtotal())
            ->setIdentifier($order->getId())
            ->setItems($this->mapLineItems($order->getLineItems()))
            // TODO: Should we always use shipping address? or maybe billing address?
            ->setDestination((string)$order->getShippingAddress());

        return $taxable;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessingClassName()
    {
        return self::PROCESSING_CLASS_NAME;
    }

    /**
     * @param Collection|OrderLineItem[] $lineItems
     * @return ArrayCollection
     */
    protected function mapLineItems($lineItems)
    {
        $taxableItems = $lineItems->map(
            function (OrderLineItem $item) {
                return $this->orderLineItemMapper->map($item);
            }
        );

        return $taxableItems;
    }
}
