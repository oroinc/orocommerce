<?php

namespace OroB2B\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;

class EnabledConfigurationValidationGroupValidator extends ConstraintValidator
{
    /**
     * @var ExecutionContextInterface $context
     */
    protected $context;

    /**
     * @param ShippingRule $value
     * @param EnabledConfigurationValidationGroup|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $enabledRules = $value->getConfigurations()->filter(function (ShippingRuleConfiguration $ruleConfiguration) {
            return $ruleConfiguration->getEnabled();
        });
        if ($enabledRules->count() === 0) {
            $this->context->buildViolation($constraint->message, ['{{ limit }}' => 1])
                ->atPath('configurations')->addViolation();
        }
    }
}
