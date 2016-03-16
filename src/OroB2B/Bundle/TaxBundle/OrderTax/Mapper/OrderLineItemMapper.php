<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class OrderLineItemMapper extends AbstractOrderMapper
{
    const PROCESSING_CLASS_NAME = 'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem';

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
            ->setCurrency($lineItem->getCurrency());

        return $taxable;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessingClassName()
    {
        return self::PROCESSING_CLASS_NAME;
    }
}
