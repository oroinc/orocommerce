<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Checker;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Checks if product kit is available for purchase.
 */
class ProductKitAvailabilityChecker
{
    private ValidatorInterface $validator;

    private array $availabilityValidationGroups;

    public function __construct(ValidatorInterface $validator, array $availabilityValidationGroups)
    {
        $this->validator = $validator;
        $this->availabilityValidationGroups = $availabilityValidationGroups;
    }

    public function isAvailable(
        Product $product,
        ?ConstraintViolationListInterface &$constraintViolationList = null
    ): bool {
        $constraintViolationList = $this->validator->validate(
            $product,
            null,
            ValidationGroupUtils::resolveValidationGroups($this->availabilityValidationGroups)
        );

        return $constraintViolationList->count() === 0;
    }
}
