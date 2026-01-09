<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

/**
 * Defines the contract for validation constraints that provide a string alias.
 *
 * This interface is used by constraints in the ValidationBundle to expose a short, human-readable alias
 * that can be used for client-side validation, form field data attributes, and constraint identification
 * in validation configuration. The alias provides a simpler alternative to using
 * the fully-qualified class name when referencing constraints in JavaScript validation rules.
 */
interface AliasAwareConstraintInterface
{
    /**
     * Get constraint alias
     *
     * @return string
     */
    public function getAlias();
}
