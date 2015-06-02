<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class QuoteProductItems extends Constraint
{
    public $message = 'This value is incorrect.';
    public $service = 'orob2b.validator.sale.product_unit';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return $this->service;
    }

    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
