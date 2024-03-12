<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator that checks if the quantity specified in a kit item line item
 *   follows the allowed precision.
 */
class ProductKitItemLineItemQuantityUnitPrecisionValidator extends ConstraintValidator
{
    private RoundingServiceInterface $roundingService;

    private UnitLabelFormatterInterface $unitLabelFormatter;

    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        RoundingServiceInterface $roundingService,
        UnitLabelFormatterInterface $unitLabelFormatter,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->roundingService = $roundingService;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemLineItemQuantityUnitPrecision) {
            throw new UnexpectedTypeException($constraint, ProductKitItemLineItemQuantityUnitPrecision::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_scalar($value)) {
            throw new UnexpectedValueException($value, 'scalar');
        }

        $kitItemLineItem = $this->context->getObject();
        if (!$kitItemLineItem instanceof ProductKitItemLineItemInterface) {
            throw new UnexpectedValueException($kitItemLineItem, ProductKitItemLineItemInterface::class);
        }

        [$unitCode, $precision] = $this->getUnitPrecision($kitItemLineItem, $constraint);
        if ($unitCode === null || $precision === null) {
            return;
        }

        if ($this->roundingService->round($value, $precision) !== $value) {
            $unitLabel = $this->unitLabelFormatter->format($unitCode);
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ unit }}', $this->formatValue($unitLabel))
                ->setParameter('{{ precision }}', $this->formatValue($precision))
                ->setParameter('%count%', $precision)
                ->setCause($value)
                ->setCode($constraint::INVALID_PRECISION)
                ->addViolation();
        }
    }

    /**
     * @param ProductKitItemLineItemInterface $kitItemLineItem
     * @param ProductKitItemLineItemQuantityUnitPrecision $constraint
     *
     * @return array<?string,?int>
     */
    private function getUnitPrecision(
        ProductKitItemLineItemInterface $kitItemLineItem,
        ProductKitItemLineItemQuantityUnitPrecision $constraint
    ): array {
        $productUnit = $kitItemLineItem->getProductUnit();
        $unitCode = $productUnit?->getCode() ?? $kitItemLineItem->getProductUnitCode();
        if ($unitCode === null) {
            return [null, null];
        }

        if ($constraint->unitPrecisionPropertyPath) {
            $precision = $this->propertyAccessor->getValue($kitItemLineItem, $constraint->unitPrecisionPropertyPath);
        } else {
            $product = $kitItemLineItem->getProduct();
            if ($product === null) {
                return [$unitCode, null];
            }

            $precision = $product->getUnitPrecision($unitCode)?->getPrecision();
            if ($precision === null && $productUnit !== null) {
                $precision = $productUnit->getDefaultPrecision();
            }
        }

        return [$unitCode, $precision];
    }
}
