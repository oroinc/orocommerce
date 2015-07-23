<?php

namespace OroB2B\Bundle\RFPBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RequestProductItem extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.rfp.requestproductitem.unit.blank';

    /**
     * @var string
     */
    public $service = 'orob2b_rfp.validator.request_product_item';

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
