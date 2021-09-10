<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Oro\Bundle\ShippingBundle\Checker\ShippingRuleEnabledCheckerInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ShippingRuleEnableValidator extends ConstraintValidator
{
    /**
     * @var ShippingRuleEnabledCheckerInterface
     */
    private $ruleEnabledChecker;

    public function __construct(ShippingRuleEnabledCheckerInterface $ruleEnabledChecker)
    {
        $this->ruleEnabledChecker = $ruleEnabledChecker;
    }

    /**
     * @param ShippingMethodsConfigsRule    $value
     * @param ShippingRuleEnable|Constraint $constraint
     *
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ShippingMethodsConfigsRule) {
            throw new UnexpectedTypeException($value, ShippingMethodsConfigsRule::class);
        }

        if (!$value->getRule()->isEnabled()) {
            return;
        }

        /** @var ExecutionContextInterface $context */
        $context = $this->context;

        if (!$this->ruleEnabledChecker->canBeEnabled($value)) {
            $context->buildViolation($constraint->message)
                ->atPath('rule')
                ->addViolation();
        }
    }
}
