<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
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
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        ShippingRuleEnabledCheckerInterface $ruleEnabledChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->ruleEnabledChecker = $ruleEnabledChecker;
        $this->tokenAccessor = $tokenAccessor;
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

        if (null === $this->tokenAccessor->getOrganization()) {
            // this validation cannot be performed when there is no organization in the security context
            // because shipping methods are related to integration channels that belong to organizations
            return;
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
