<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator checking that only a product of type {@see \Oro\Bundle\ProductBundle\Entity\Product::TYPE_KIT}
 *  can have kitItems.
 */
class OnlyProductKitCanHaveKitItemsValidator extends ConstraintValidator
{
    /**
     * @param Product|null $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof OnlyProductKitCanHaveKitItems) {
            throw new UnexpectedTypeException($constraint, OnlyProductKitCanHaveKitItems::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        if ($value->getType() !== Product::TYPE_KIT) {
            $kitItems = $value->getKitItems();
            if ($kitItems instanceof PersistentCollection) {
                if ($constraint->forceInitialize) {
                    $kitItems->initialize();
                } else {
                    $kitItems = $kitItems->unwrap();
                }
            }

            if ($kitItems->count() > 0) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->setParameter('{{ sku }}', $this->formatValue($value->getSku()))
                    ->setCause($value)
                    ->setInvalidValue($value->getType())
                    ->setCode($constraint::MUST_BE_PRODUCT_KIT)
                    ->addViolation();
            }
        }
    }
}
