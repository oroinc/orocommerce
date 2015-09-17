<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductUnitHolder extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.product.productunitholder.product_unit.blank';

    /**
     * @var string
     */
    public $service = 'orob2b_product.validator.product_unit_holder';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}
