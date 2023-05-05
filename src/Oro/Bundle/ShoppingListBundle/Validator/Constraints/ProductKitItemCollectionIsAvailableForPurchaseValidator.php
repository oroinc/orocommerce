<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that collection of {@see ProductKitItem} entities is available for purchase.
 */
class ProductKitItemCollectionIsAvailableForPurchaseValidator extends ConstraintValidator
{
    private LocalizationHelper $localizationHelper;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param iterable<ProductKitItem>|null $value
     * @param ProductKitItemCollectionIsAvailableForPurchase $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemCollectionIsAvailableForPurchase) {
            throw new UnexpectedTypeException($constraint, ProductKitItemCollectionIsAvailableForPurchase::class);
        }

        if (!is_iterable($value)) {
            throw new UnexpectedValueException($value, 'iterable');
        }

        $validator = $this->context->getValidator();
        $kitItemsCount = $unavailableKitItemsCount = 0;
        $productKitSku = null;
        foreach ($value as $kitItem) {
            $kitItemsCount++;
            $constraintViolations = $validator->validate($value, null, ['product_kit_item_is_available_for_purchase']);
            if ($constraintViolations->count() > 0) {
                $unavailableKitItemsCount++;

                if ($productKitSku === null) {
                    $productKitSku = (string)$kitItem->getProductKit()?->getSku();
                }

                if ($kitItem->isOptional() === false) {
                    $kitItemLabel = (string)$this->localizationHelper->getLocalizedValue($kitItem->getLabels());
                    $reason = array_map(
                        static fn (ConstraintViolationInterface $violation) => $violation->getMessage(),
                        iterator_to_array($constraintViolations)
                    );
                    $this->context
                        ->buildViolation($constraint->requiredKitItemNotAvailableMessage)
                        ->setParameter('{{ product_kit_item_label }}', $this->formatValue($kitItemLabel))
                        ->setParameter('{{ product_kit_sku }}', $this->formatValue($productKitSku))
                        ->setParameter('{{ reason }}', $this->formatValues($reason))
                        ->setCode(ProductKitItemCollectionIsAvailableForPurchase::REQUIRED_KIT_ITEM_NOT_AVAILABLE_ERROR)
                        ->setCause($constraintViolations)
                        ->addViolation();
                }
            }
        }

        if ($kitItemsCount === $unavailableKitItemsCount && $this->context->getViolations()->count() === 0) {
            $this->context
                ->buildViolation($constraint->noAvailableKitItemsMessage)
                ->setCode(ProductKitItemCollectionIsAvailableForPurchase::NO_AVAILABLE_KIT_ITEMS_ERROR)
                ->addViolation();
        }
    }
}
