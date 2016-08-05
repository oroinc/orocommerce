<?php

namespace OroB2B\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;

class EnabledConfigurationValidationGroupValidator extends ConstraintValidator
{
    /**
     * @var ExecutionContextInterface $context
     */
    protected $context;

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
                    'OroB2B\Bundle\ShippingBundle\Model\ShippingRuleConfiguration'
                );
            }
            if ($configuration->getEnabled()) {
                $enabledRules[] = $configuration;
            }
        }

        if (count($enabledRules) < $constraint->min) {
            $this->context->buildViolation($constraint->message, ['{{ limit }}' => $constraint->min])
                ->atPath('configurations')->addViolation();
        }
    }
}
