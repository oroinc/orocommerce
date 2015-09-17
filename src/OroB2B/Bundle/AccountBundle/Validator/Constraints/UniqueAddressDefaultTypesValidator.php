<?php

namespace OroB2B\Bundle\AccountBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress;

class UniqueAddressDefaultTypesValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !($value instanceof \Traversable && $value instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and ArrayAccess');
        }

        $repeatedTypes = [];
        $collectedTypes = [];

        /** @var AbstractDefaultTypedAddress $address */
        foreach ($value as $address) {
            if (!$address instanceof AbstractDefaultTypedAddress) {
                throw new UnexpectedTypeException(
                    $value,
                    'OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress'
                );
            }

            if ($address->isEmpty()) {
                continue;
            }

            foreach ($address->getDefaults() as $type) {
                if (isset($collectedTypes[$type->getName()])) {
                    $repeatedTypes[] = $type->getLabel();
                }

                $collectedTypes[$type->getName()] = true;
            }
        }

        if ($repeatedTypes) {
            /** @var UniqueAddressDefaultTypes $constraint */
            $this->context->addViolation(
                $constraint->message,
                array('{{ types }}' => '"' . implode('", "', $repeatedTypes) . '"')
            );
        }
    }
}
