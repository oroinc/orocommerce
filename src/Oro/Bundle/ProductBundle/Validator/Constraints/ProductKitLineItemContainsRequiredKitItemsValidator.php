<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator that checks if a product kit line item contains all required kit item line items.
 */
class ProductKitLineItemContainsRequiredKitItemsValidator extends ConstraintValidator
{
    private LocalizationHelper $localizationHelper;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param ProductKitItemLineItemsAwareInterface|null $value
     * @param ProductKitLineItemContainsRequiredKitItems $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitLineItemContainsRequiredKitItems) {
            throw new UnexpectedTypeException($constraint, ProductKitLineItemContainsRequiredKitItems::class);
        }

        if ($value?->getProduct()?->isKit() !== true) {
            return;
        }

        if (!$value instanceof ProductKitItemLineItemsAwareInterface) {
            throw new UnexpectedValueException($value, ProductKitItemLineItemsAwareInterface::class);
        }

        $requiredKitItemLineItems = $this->getPresentRequiredKitItems($value->getKitItemLineItems());

        $localization = $this->localizationHelper->getCurrentLocalization();
        foreach ($value->getProduct()->getKitItems() as $kitItem) {
            if ($kitItem->isOptional()) {
                continue;
            }

            if (!isset($requiredKitItemLineItems[spl_object_hash($kitItem)])) {
                $kitItemLabel = (string)$this->localizationHelper
                    ->getLocalizedValue($kitItem->getLabels(), $localization);
                $productSku = $value->getProduct()->getSku();

                $this->context->buildViolation(
                    $constraint->message,
                    [
                        '{{ product_kit_sku }}' => $this->formatValue($productSku),
                        '{{ product_kit_item_label }}' => $this->formatValue($kitItemLabel),
                    ]
                )
                    ->atPath('kitItemLineItems')
                    ->setCause($value)
                    ->setCode(ProductKitLineItemContainsRequiredKitItems::MISSING_REQUIRED_KIT_ITEM)
                    ->addViolation();
            }
        }
    }

    /**
     * @param Collection<ProductKitItemLineItemInterface> $kitItemLineItems
     *
     * @return array<ProductKitItem>
     */
    private function getPresentRequiredKitItems(Collection $kitItemLineItems): array
    {
        $requiredKitItems = [];
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $kitItem = $kitItemLineItem->getKitItem();
            if ($kitItem === null || $kitItem->isOptional()) {
                continue;
            }

            $requiredKitItems[spl_object_hash($kitItem)] = $kitItem;
        }

        return $requiredKitItems;
    }
}
