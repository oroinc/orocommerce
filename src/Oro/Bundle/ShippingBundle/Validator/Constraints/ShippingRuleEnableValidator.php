<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Oro\Bundle\ShippingBundle\Checker\ShippingRuleEnabledCheckerInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator checks whether a shipping rule can be enabled.
 */
class ShippingRuleEnableValidator extends ConstraintValidator
{
    private ShippingRuleEnabledCheckerInterface $ruleEnabledChecker;

    public function __construct(ShippingRuleEnabledCheckerInterface $ruleEnabledChecker)
    {
        $this->ruleEnabledChecker = $ruleEnabledChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ShippingRuleEnable) {
            throw new UnexpectedTypeException($constraint, ShippingRuleEnable::class);
        }

        if (!$value instanceof ShippingMethodsConfigsRule) {
            throw new UnexpectedTypeException($value, ShippingMethodsConfigsRule::class);
        }

        if (!$value->getRule()->isEnabled()) {
            return;
        }

        if (!$this->ruleEnabledChecker->canBeEnabled($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('rule')
                ->addViolation();
        }
    }
}
