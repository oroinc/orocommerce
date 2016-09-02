<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EnabledTypeConfigsValidationGroupValidator extends ConstraintValidator
{
    /**
     * @param array $value
     * @param EnabledTypeConfigsValidationGroup|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !($value instanceof \Traversable && $value instanceof \Countable)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and Countable');
        }

        $enabledRules = [];
        foreach ($value as $config) {
            if (!$config instanceof ShippingRuleMethodTypeConfig) {
                throw new UnexpectedTypeException(
                    $config,
                    'Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig'
                );
            }
            if ($config->isEnabled()) {
                $enabledRules[] = $config;
            }
        }

        if (count($enabledRules) < $constraint->min) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->message, ['{{ limit }}' => $constraint->min])
                    ->atPath('configurations')
                    ->addViolation();
            } else {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ limit }}', $constraint->min)
                    ->atPath('configurations')
                    ->addViolation();
            }
        }
    }
}
