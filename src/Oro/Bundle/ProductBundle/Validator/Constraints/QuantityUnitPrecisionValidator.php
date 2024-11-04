<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a product quantity is valid based on a product unit
 * of a product associated with the validating value.
 */
class QuantityUnitPrecisionValidator extends ConstraintValidator
{
    public const ALIAS = 'oro_product_quantity_unit_precision';

    private RoundingServiceInterface $roundingService;

    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof QuantityUnitPrecision) {
            throw new UnexpectedTypeException($constraint, QuantityUnitPrecision::class);
        }

        if (null === $value) {
            return;
        }

        $quantity = $this->getQuantity($value);
        if (null === $quantity) {
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

        $precision = $this->getPrecision($value, $product, $unitCode);
        if (null === $precision) {
            return;
        }

        if ($this->roundingService->round($quantity, $precision) !== $quantity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ unit }}', $unitCode)
                ->atPath($constraint->path)
                ->addViolation();
        }
    }

    private function getPrecision(object $value, Product $product, string $unitCode): ?int
    {
        $unitPrecision = $product->getUnitPrecision($unitCode);
        if (null !== $unitPrecision) {
            return $unitPrecision->getPrecision();
        }

        return $this->getProductUnit($value)?->getDefaultPrecision();
    }

    private function getProductUnit(object $value): ?ProductUnit
    {
        $unit = $value instanceof ProductUnitHolderInterface
            ? $value->getProductUnit()
            : $value->getUnit();
        if (!$unit instanceof ProductUnit) {
            return null;
        }

        return $unit;
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

    private function getQuantity(object $value): ?float
    {
        $quantity = $value->getQuantity();
        if (\is_float($quantity) || \is_int($quantity)) {
            return $quantity;
        }
        if (null === $quantity || '' === $quantity || !\is_string($quantity)) {
            return null;
        }

        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
        $parsedQuantity = $formatter->parse($quantity, \NumberFormatter::TYPE_DOUBLE);
        if (false === $parsedQuantity) {
            return null;
        }

        return $parsedQuantity;
    }

    private function getProduct(object $value): ?Product
    {
        return $value->getProduct();
    }
}
