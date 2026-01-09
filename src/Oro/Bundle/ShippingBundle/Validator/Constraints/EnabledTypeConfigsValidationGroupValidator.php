<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for {@see EnabledTypeConfigsValidationGroup} constraint.
 *
 * This validator counts the number of enabled shipping method type configurations and ensures that
 * at least the minimum required number are enabled, adding a validation violation if the requirement is not met.
 */
class EnabledTypeConfigsValidationGroupValidator extends ConstraintValidator
{
    /**
     * @param array $value
     * @param EnabledTypeConfigsValidationGroup|Constraint $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !($value instanceof \Traversable && $value instanceof \Countable)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and Countable');
        }

        $enabledRules = [];
        foreach ($value as $config) {
            if (!$config instanceof ShippingMethodTypeConfig) {
                throw new UnexpectedTypeException(
                    $config,
                    'Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig'
                );
            }
            if ($config->isEnabled()) {
                $enabledRules[] = $config;
            }
        }

        $count = count($enabledRules);

        if ($count < $constraint->min) {
            $builder = $this->context->buildViolation($constraint->message);
            $builder
                ->setParameter('{{ count }}', $count)
                ->setParameter('{{ limit }}', $constraint->min)
                ->setPlural((int)$constraint->min)
                ->atPath('configurations')
                ->addViolation();
        }
    }
}
