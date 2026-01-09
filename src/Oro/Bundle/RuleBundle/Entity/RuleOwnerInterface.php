<?php

namespace Oro\Bundle\RuleBundle\Entity;

/**
 * Defines the contract for entities that own or contain rules.
 *
 * This interface is implemented by entities that have an associated rule. It provides a simple contract
 * for retrieving the rule instance from the owner entity. This pattern allows other bundles to create domain-specific
 * entities that leverage the rule engine by implementing this interface and associating themselves with rule instances.
 */
interface RuleOwnerInterface
{
    /**
     * @return RuleInterface
     */
    public function getRule();
}
