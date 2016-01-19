<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class OrderLineItemMapper extends AbstractOrderMapper
{
    const PROCESSING_CLASS_NAME = 'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem';

    /**
     * {@inheritdoc}
     * @param OrderLineItem $lineItem
     */
    public function map($lineItem)
    {
        $taxable = (new Taxable())
            ->setIdentifier($lineItem->getId())
            ->setClassName($this->getProcessingClassName())
            ->setQuantity($lineItem->getQuantity())
            ->setDestination($this->getOrderAddress($lineItem->getOrder()))
            ->setPrice($lineItem->getPrice());

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
