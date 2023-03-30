<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that each {@see ProductKitItem} in {@see Product::$kitItems} collection is owned by product kit.
 */
class ProductKitItemsOwnedByProductKitValidator extends ConstraintValidator
{
    private LocalizationHelper $localizationHelper;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param Product|null $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemsOwnedByProductKit) {
            throw new UnexpectedTypeException($constraint, ProductKitItemsOwnedByProductKit::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        if ($value->getType() !== Product::TYPE_KIT) {
            return;
        }

        foreach ($value->getKitItems() as $index => $kitItem) {
            if (spl_object_hash($kitItem->getProductKit()) !== spl_object_hash($value)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter(
                        '{{ kit_item_label }}',
                        $this->formatValue((string)$this->localizationHelper->getLocalizedValue($kitItem->getLabels()))
                    )
                    ->setParameter('{{ product_kit_sku }}', $this->formatValue($kitItem->getProductKit()?->getSku()))
                    ->atPath('kitItems.' . $index)
                    ->setCode(ProductKitItemsOwnedByProductKit::KIT_ITEM_IS_NOT_OWNED_ERROR)
                    ->addViolation();
            }
        }
    }
}
