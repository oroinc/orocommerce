<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Checker;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Checks if the product of a product kit item is available for purchase.
 */
class ProductKitItemProductAvailabilityChecker
{
    private ValidatorInterface $validator;

    private array $availabilityValidationGroups;

    public function __construct(ValidatorInterface $validator, array $availabilityValidationGroups)
    {
        $this->validator = $validator;
        $this->availabilityValidationGroups = $availabilityValidationGroups;
    }

    public function isAvailable(
        ProductKitItemProduct $productKitItemProduct,
        ?ConstraintViolationListInterface &$constraintViolationList = null
    ): bool {
        $constraintViolationList = $this->validator->validate(
            $productKitItemProduct,
            null,
            ValidationGroupUtils::resolveValidationGroups($this->availabilityValidationGroups)
        );

        return $constraintViolationList->count() === 0;
    }
}
