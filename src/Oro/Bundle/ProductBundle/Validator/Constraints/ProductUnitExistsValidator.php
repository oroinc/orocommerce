<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a product unit exists in a list of available product units
 * for a product associated with the validating value.
 */
class ProductUnitExistsValidator extends ConstraintValidator
{
    public const ALIAS = 'oro_product_product_unit_exists';

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductUnitExists) {
            throw new UnexpectedTypeException($constraint, ProductUnitExists::class);
        }

        if (null === $value) {
            return;
        }

        $unitCode = $this->getUnitCode($value);
        if (null === $unitCode || '' === $unitCode) {
            return;
        }

        $product = $this->getProduct($value);
        if (null === $product) {
            return;
        }

        if (!$this->isValid($unitCode, $product, $constraint->sell)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ unit }}', $unitCode)
                ->setParameter('{{ sku }}', $product->getSku())
                ->atPath($constraint->path)
                ->addViolation();
        }
    }

    private function isValid(string $unitCode, Product $product, bool $isSell): bool
    {
        $unitPrecisions = $product->getUnitPrecisions();
        foreach ($unitPrecisions as $unitPrecision) {
            $productUnit = $unitPrecision->getUnit();
            if (null === $productUnit || $productUnit->getCode() !== $unitCode) {
                continue;
            }
            if ($isSell && !$unitPrecision->isSell()) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function getUnitCode(object $value): ?string
    {
        $unit = $value instanceof ProductUnitHolderInterface
            ? $value->getProductUnit()
            : $value->getUnit();
        if ($unit instanceof ProductUnit) {
            $unit = $unit->getCode();
        }

        return $unit;
    }

    private function getProduct(object $value): ?Product
    {
        return $value->getProduct();
    }
}
