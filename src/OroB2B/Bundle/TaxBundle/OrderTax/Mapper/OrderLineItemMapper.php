<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class OrderLineItemMapper implements TaxMapperInterface
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
            ->setQuantity($lineItem->getQuantity())
            ->setPrice($lineItem->getValue() ?: 0);

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
