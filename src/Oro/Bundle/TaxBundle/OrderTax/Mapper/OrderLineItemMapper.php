<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Mapper;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Creates Taxable object from OrderLineItem entity.
 */
class OrderLineItemMapper extends AbstractOrderMapper
{
    /**
     * {@inheritdoc}
     * @param OrderLineItem $lineItem
     */
    public function map($lineItem)
    {
        $order = $lineItem->getOrder();

        $quantity = $lineItem->getQuantity() === null ? 0 : $lineItem->getQuantity();
        $taxable = (new Taxable())
            ->setIdentifier($lineItem->getId())
            ->setClassName(OrderLineItem::class)
            ->setQuantity($quantity)
            ->setOrigin($this->addressProvider->getOriginAddress())
            ->setDestination($this->getDestinationAddress($order))
            ->setTaxationAddress($this->getTaxationAddress($order))
            ->setContext($this->getContext($lineItem));

        if ($lineItem->getPrice()) {
            $taxable->setPrice($lineItem->getPrice()->getValue());
            $taxable->setCurrency($lineItem->getPrice()->getCurrency());
        }

        return $taxable;
    }
}
