<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Checker;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Checks if product kit is available for purchase.
 */
class ProductKitAvailabilityChecker
{
    private ValidatorInterface $validator;

    private array $validationGroups = ['product_kit_is_available_for_purchase'];

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function setValidationGroups(array $validationGroups): void
    {
        $this->validationGroups = $validationGroups;
    }

    public function isAvailableForPurchase(
        Product $product,
        ConstraintViolationListInterface &$constraintViolationList = null
    ): bool {
        $constraintViolationList = $this->validator->validate($product, null, $this->validationGroups);

        return $constraintViolationList->count() === 0;
    }
}
