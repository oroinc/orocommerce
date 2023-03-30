<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Checker;

use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Checks if the product of a product kit item is available for purchase.
 */
class ProductKitItemProductAvailabilityChecker
{
    private ValidatorInterface $validator;

    private array $validationGroups = ['product_kit_item_product_is_available_for_purchase'];

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function setValidationGroups(array $validationGroups): void
    {
        $this->validationGroups = $validationGroups;
    }

    public function isAvailableForPurchase(
        ProductKitItemProduct $productKitItemProduct,
        ConstraintViolationListInterface &$constraintViolationList = null
    ): bool {
        $constraintViolationList = $this->validator->validate($productKitItemProduct, null, $this->validationGroups);

        return $constraintViolationList->count() === 0;
    }
}
