<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

class ProductUnitHolderValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param ProductUnitHolderInterface $productUnitHolder
     * @param ProductUnitHolder $constraint
     */
    public function validate($productUnitHolder, Constraint $constraint)
    {
        if (!$productUnitHolder instanceof ProductUnitHolderInterface) {
            throw new UnexpectedTypeException(
                $productUnitHolder,
                'OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface'
            );
        }

        if (null === ($productHolder = $productUnitHolder->getProductHolder())) {
            $this->addViolation($constraint);

            return;
        }

        $product = $productHolder->getProduct();

        if (null === $product) {
            $this->addViolation($constraint);

            return;
        }

        if ([] === ($allowedUnits = $product->getAvailableUnitCodes())) {
            $this->addViolation($constraint);

            return;
        }

        if (null === ($productUnit = $productUnitHolder->getProductUnit())) {
            $this->addViolation($constraint);

            return;
        }

        if (!in_array($productUnit->getCode(), $allowedUnits, true)) {
            $this->addViolation($constraint);

            return;
        }
    }

    /**
     * @param ProductUnitHolder $constraint
     */
    protected function addViolation(ProductUnitHolder $constraint)
    {
        $this->context->addViolationAt('productUnit', $constraint->message);
    }
}
