<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Checker;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Checks if product kit item is available for purchase.
 */
class ProductKitItemAvailabilityChecker
{
    private ValidatorInterface $validator;

    private array $availabilityValidationGroups;

    public function __construct(ValidatorInterface $validator, array $availabilityValidationGroups)
    {
        $this->validator = $validator;
        $this->availabilityValidationGroups = $availabilityValidationGroups;
    }

    public function isAvailable(
        ProductKitItem $productKitItem,
        ?ConstraintViolationListInterface &$constraintViolationList = null
    ): bool {
        $constraintViolationList = $this->validator->validate(
            $productKitItem,
            null,
            ValidationGroupUtils::resolveValidationGroups($this->availabilityValidationGroups)
        );

        return $constraintViolationList->count() === 0;
    }
}
