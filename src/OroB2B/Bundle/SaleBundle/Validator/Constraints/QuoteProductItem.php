<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class QuoteProductItem extends Constraint
{
    public $message = 'This value is not valid.';
    public $service = 'orob2b_sale.validator.quote_product_unit';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return $this->service;
    }

    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}
