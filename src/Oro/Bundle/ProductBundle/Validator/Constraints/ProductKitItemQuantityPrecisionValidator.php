<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that {@see ProductKitItem} quantity follows the precision
 */
class ProductKitItemQuantityPrecisionValidator extends ConstraintValidator
{
    private ProductKitItemProductUnitChecker $productUnitChecker;

    private RoundingServiceInterface $roundingService;

    public function __construct(
        ProductKitItemProductUnitChecker $productUnitChecker,
        RoundingServiceInterface $roundingService
    ) {
        $this->productUnitChecker = $productUnitChecker;
        $this->roundingService = $roundingService;
    }

    /**
     * @param ProductKitItem|null $value
     * @param ProductKitItemUnitAvailableForSpecifiedProducts $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemQuantityPrecision) {
            throw new UnexpectedTypeException($constraint, ProductKitItemQuantityPrecision::class);
        }

        if (!$value instanceof ProductKitItem) {
            throw new UnexpectedValueException($value, ProductKitItem::class);
        }

        $productUnit = $value->getProductUnit();
        if (!$productUnit) {
            // Skips further execution as product unit is not specified.
            return;
        }

        $unitPrecisions = $this->productUnitChecker
            ->getEligibleProductUnitPrecisions($productUnit->getCode(), $value->getProducts());
        if (!$unitPrecisions) {
            // Skips further execution as there are no eligible product unit precisions.
            return;
        }

        $unitMinimumPrecision = $this->getUnitMinimumPrecision($unitPrecisions);

        $minimumQuantity = $value->getMinimumQuantity();
        if ($minimumQuantity) {
            $roundedQuantity = $this->roundingService->round($minimumQuantity, $unitMinimumPrecision);
            if ($roundedQuantity !== $minimumQuantity) {
                $this->context->buildViolation($constraint->minimumQuantityMessage)
                    ->setParameter('{{ value }}', $this->formatValue($minimumQuantity))
                    ->setParameter('{{ precision }}', $this->formatValue($unitMinimumPrecision))
                    ->atPath('minimumQuantity')
                    ->setCode(ProductKitItemQuantityPrecision::MINIMUM_QUANTITY_PRECISION_ERROR)
                    ->addViolation();
            }
        }

        $maximumQuantity = $value->getMaximumQuantity();
        if ($value->getMaximumQuantity()) {
            $roundedQuantity = $this->roundingService->round($maximumQuantity, $unitMinimumPrecision);
            if ($roundedQuantity !== $maximumQuantity) {
                $this->context->buildViolation($constraint->maximumQuantityMessage)
                    ->setParameter('{{ value }}', $this->formatValue($value->getMaximumQuantity()))
                    ->setParameter('{{ precision }}', $this->formatValue($unitMinimumPrecision))
                    ->atPath('maximumQuantity')
                    ->setCode(ProductKitItemQuantityPrecision::MAXIMUM_QUANTITY_PRECISION_ERROR)
                    ->addViolation();
            }
        }
    }

    /**
     * @param ProductUnitPrecision[] $unitPrecisions
     * @return int
     */
    private function getUnitMinimumPrecision(array $unitPrecisions): int
    {
        $unitMinimumPrecision = PHP_INT_MAX;
        foreach ($unitPrecisions as $unitPrecision) {
            $unitMinimumPrecision = min($unitMinimumPrecision, $unitPrecision->getPrecision());
        }

        return $unitMinimumPrecision;
    }
}
