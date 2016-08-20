<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Mapper;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Model\Taxable;

class OrderLineItemMapper extends AbstractOrderMapper
{
    /**
     * @param OrderLineItem $lineItem
     *
     * {@inheritdoc}
     */
    public function map($lineItem)
    {
        $order = $lineItem->getOrder();
        $taxable = (new Taxable())
            ->setIdentifier($lineItem->getId())
            ->setClassName($this->getProcessingClassName())
            ->setQuantity($lineItem->getQuantity())
            ->setOrigin($this->addressProvider->getOriginAddress())
            ->setDestination($this->getDestinationAddress($order))
            ->setTaxationAddress($this->getTaxationAddress($order))
            ->setContext($this->getContext($lineItem))
            ->setPrice($lineItem->getPrice() ? $lineItem->getPrice()->getValue() : null)
            ->setCurrency($lineItem->getPrice() ? $lineItem->getPrice()->getCurrency() : null);

        return $taxable;
    }
}
