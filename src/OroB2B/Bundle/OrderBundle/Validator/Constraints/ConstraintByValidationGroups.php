<?php

namespace OroB2B\Bundle\OrderBundle\Validator\Constraints;

interface ConstraintByValidationGroups
{
    /**
     * @return array
     */
    public function getValidationGroups();
}
