<?php

namespace OroB2B\Bundle\TaxBundle\OrderTax\Mapper;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;

class OrderMapper implements TaxMapperInterface
{
    const PROCESSING_CLASS_NAME = 'OroB2B\Bundle\OrderBundle\Entity\Order';

    /**
     * {@inheritdoc}
     * @param Order $object
     */
    public function map($object)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessingClassName()
    {
        return self::PROCESSING_CLASS_NAME;
    }
}
