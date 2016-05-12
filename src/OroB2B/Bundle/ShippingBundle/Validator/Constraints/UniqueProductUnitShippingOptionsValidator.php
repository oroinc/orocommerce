<?php

namespace OroB2B\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

class UniqueProductUnitShippingOptionsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !($value instanceof \Traversable && $value instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and ArrayAccess');
        }

        $codes = [];

        foreach ($value as $option) {
            if (!$option instanceof ProductUnitHolderInterface) {
                throw new UnexpectedTypeException(
                    $option,
                    'OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface'
                );
            }

            $code = $option->getProductUnit()->getCode();

            if (array_key_exists($code, $codes)) {
                /** @var UniqueProductUnitShippingOptions $constraint */
                $this->context->addViolation($constraint->message);
                break;
            } else {
                $codes[$code] = $option;
            }
        }
    }
}
