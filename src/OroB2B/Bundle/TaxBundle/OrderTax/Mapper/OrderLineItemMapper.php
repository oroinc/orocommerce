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
        $orderAddress = $this->getOrderAddress($lineItem->getOrder());

        $taxable = (new Taxable())
            ->setIdentifier($lineItem->getId())
            ->setClassName($this->getProcessingClassName())
            ->setQuantity($lineItem->getQuantity())
            ->setDestination($orderAddress)
            ->setContext($this->getContext($lineItem))
            ->setCurrency($lineItem->getCurrency());

        if ($lineItem->getPrice()) {
            $taxable->setPrice($lineItem->getPrice()->getValue());
        }

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
