<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that collection of {@see ProductKitItemProduct} entities is available for purchase.
 */
class ProductKitItemProductCollectionIsAvailableForPurchaseValidator extends ConstraintValidator
{
    private LocalizationHelper $localizationHelper;

    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param iterable<ProductKitItemProduct>|null $value
     * @param ProductKitItemProductCollectionIsAvailableForPurchase $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemProductCollectionIsAvailableForPurchase) {
            throw new UnexpectedTypeException(
                $constraint,
                ProductKitItemProductCollectionIsAvailableForPurchase::class
            );
        }

        if (!is_iterable($value)) {
            throw new UnexpectedValueException($value, 'iterable');
        }

        $validator = $this->context->getValidator();
        $productsCount = $unavailableProductsCount = 0;
        $kitItemLabel = null;
        foreach ($value as $kitItemProduct) {
            $productsCount++;
            $constraintViolations = $validator->validate(
                $kitItemProduct,
                null,
                ['product_kit_item_product_is_available_for_purchase']
            );
            if ($constraintViolations->count() > 0) {
                $unavailableProductsCount++;

                if ($kitItemLabel === null) {
                    $kitItemLabel = (string)$this->localizationHelper
                        ->getLocalizedValue($kitItemProduct->getKitItem()?->getLabels());
                }
            }
        }

        if ($productsCount === 0) {
            $this->context
                ->buildViolation($constraint->emptyMessage)
                ->setCode(ProductKitItemProductCollectionIsAvailableForPurchase::NO_AVAILABLE_PRODUCTS_ERROR)
                ->addViolation();
        } elseif ($productsCount === $unavailableProductsCount) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ product_kit_item_label }}', $this->formatValue($kitItemLabel))
                ->setCode(ProductKitItemProductCollectionIsAvailableForPurchase::NO_AVAILABLE_PRODUCTS_ERROR)
                ->addViolation();
        }
    }
}
