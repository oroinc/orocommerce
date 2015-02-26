<?php

namespace OroB2B\Bundle\ValidationBundle\Validator\Constraints;

interface AliasAwareConstraintInterface
{
    /**
     * Get constraint alias
     *
     * @return string
     */
    public function getAlias();
}
