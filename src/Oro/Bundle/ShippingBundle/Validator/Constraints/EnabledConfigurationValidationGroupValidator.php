<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;

class EnabledConfigurationValidationGroupValidator extends ConstraintValidator
{
    /**
     * @param array $value
     * @param EnabledConfigurationValidationGroup|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !($value instanceof \Traversable && $value instanceof \Countable)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and Countable');
        }

        $enabledRules = [];
        foreach ($value as $configuration) {
            if (!$configuration instanceof ShippingRuleConfiguration) {
                throw new UnexpectedTypeException(
                    $configuration,
                    'Oro\Bundle\ShippingBundle\Model\ShippingRuleConfiguration'
                );
            }
            if ($configuration->isEnabled()) {
                $enabledRules[] = $configuration;
            }
        }

        if (count($enabledRules) < $constraint->min) {
            $this->context->buildViolation($constraint->message, ['{{ limit }}' => $constraint->min])
                ->atPath('configurations')->addViolation();
        }
    }
}
