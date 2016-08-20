<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

interface AliasAwareConstraintInterface
{
    /**
     * Get constraint alias
     *
     * @return string
     */
    public function getAlias();
}
