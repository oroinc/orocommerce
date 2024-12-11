<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates that a product kit line item contains all required kit item line items.
 */
class ProductKitLineItemContainsRequiredKitItemsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly LocalizationHelper $localizationHelper
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitLineItemContainsRequiredKitItems) {
            throw new UnexpectedTypeException($constraint, ProductKitLineItemContainsRequiredKitItems::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ProductKitItemLineItemsAwareInterface) {
            throw new UnexpectedValueException($value, ProductKitItemLineItemsAwareInterface::class);
        }

        $product = $value->getProduct();
        if (null === $product) {
            return;
        }

        if (!$product->isKit()) {
            return;
        }

        if ($this->hasKitItemLineItemsWithoutKitItem($value->getKitItemLineItems())) {
            return;
        }

        $requiredKitItemLineItems = $this->getPresentRequiredKitItems($value->getKitItemLineItems());
        $localization = $this->localizationHelper->getCurrentLocalization();
        foreach ($product->getKitItems() as $kitItem) {
            if ($kitItem->isOptional()) {
                continue;
            }
            if (!isset($requiredKitItemLineItems[spl_object_hash($kitItem)])) {
                $this->context
                    ->buildViolation($constraint->message, [
                        '{{ product_kit_sku }}' => $this->formatValue($product->getSku()),
                        '{{ product_kit_item_label }}' => $this->formatValue(
                            (string)$this->localizationHelper->getLocalizedValue($kitItem->getLabels(), $localization)
                        )
                    ])
                    ->atPath('kitItemLineItems')
                    ->setCause($value)
                    ->setCode(ProductKitLineItemContainsRequiredKitItems::MISSING_REQUIRED_KIT_ITEM)
                    ->addViolation();
            }
        }
    }

    private function hasKitItemLineItemsWithoutKitItem(Collection $kitItemLineItems): bool
    {
        /** @var ProductKitItemLineItemInterface $kitItemLineItem */
        foreach ($kitItemLineItems as $kitItemLineItem) {
            if (null === $kitItemLineItem->getKitItem()) {
                return true;
            }
        }

        return false;
    }

    private function getPresentRequiredKitItems(Collection $kitItemLineItems): array
    {
        $requiredKitItems = [];
        /** @var ProductKitItemLineItemInterface $kitItemLineItem */
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $kitItem = $kitItemLineItem->getKitItem();
            if (null === $kitItem || $kitItem->isOptional()) {
                continue;
            }
            $requiredKitItems[spl_object_hash($kitItem)] = true;
        }

        return $requiredKitItems;
    }
}
