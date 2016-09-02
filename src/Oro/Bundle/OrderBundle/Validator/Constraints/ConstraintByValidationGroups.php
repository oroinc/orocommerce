<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

interface ConstraintByValidationGroups
{
    /**
     * @return array
     */
    public function getValidationGroups();
}
