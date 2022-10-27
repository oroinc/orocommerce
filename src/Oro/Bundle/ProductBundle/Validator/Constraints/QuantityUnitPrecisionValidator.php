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
    const ALIAS = 'oro_product_quantity_unit_precision';

    /** @var RoundingServiceInterface */
    private $roundingService;

    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
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

    /**
     * @param object  $value
     * @param Product $product
     * @param string  $unitCode
     *
     * @return int|null
     */
    private function getPrecision($value, Product $product, string $unitCode): ?int
    {
        $unitPrecision = $product->getUnitPrecision($unitCode);
        if (null !== $unitPrecision) {
            return $unitPrecision->getPrecision();
        }

        $unit = $this->getProductUnit($value);
        if (null !== $unit) {
            return $unit->getDefaultPrecision();
        }

        return null;
    }

    /**
     * @param object $value
     *
     * @return ProductUnit|null
     */
    private function getProductUnit($value): ?ProductUnit
    {
        $unit = $value instanceof ProductUnitHolderInterface
            ? $value->getProductUnit()
            : $value->getUnit();
        if (!$unit instanceof ProductUnit) {
            return null;
        }

        return $unit;
    }

    /**
     * @param object $value
     *
     * @return string|null
     */
    private function getUnitCode($value): ?string
    {
        $unit = $value instanceof ProductUnitHolderInterface
            ? $value->getProductUnit()
            : $value->getUnit();
        if ($unit instanceof ProductUnit) {
            $unit = $unit->getCode();
        }

        return $unit;
    }

    /**
     * @param object $value
     *
     * @return float|null
     */
    private function getQuantity($value): ?float
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

    /**
     * @param object $value
     *
     * @return Product|null
     */
    private function getProduct($value): ?Product
    {
        return $value->getProduct();
    }
}
