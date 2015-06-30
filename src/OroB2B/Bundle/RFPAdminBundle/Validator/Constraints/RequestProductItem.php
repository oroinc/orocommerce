<?php

namespace OroB2B\Bundle\RFPAdminBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RequestProductItem extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.rfpadmin.requestproductitem.unit.blank';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}
