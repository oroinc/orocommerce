<?php

namespace OroB2B\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

class UniqueProductUnitShippingOptionsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $ids = [];

        foreach ($value as $item) {
            if (null === $id = $this->getProductUnitId($item)) {
                continue;
            }

            if (in_array($id, $ids, true)) {
                $this->context->addViolation($constraint->message);
                break;
            }
            $ids[] = $id;
        }
    }

    private function getProductUnitId($item)
    {
        if ($item instanceof ProductUnitHolderInterface && $item->getProductUnit()) {
            return $item->getProductUnit()->getCode();
        }
    }
}
