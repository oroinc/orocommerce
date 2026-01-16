<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Provider;

/**
 * Interface for validation group resolvers that determine which validation groups are applicable
 * based on business rules (e.g., checkout, RFQ).
 */
interface ShoppingListValidationGroupResolverInterface
{
    /**
     * Returns the type of validation group (e.g., CHECKOUT, RFQ).
     */
    public function getType(): string;

    /**
     * Determines if this validation group is applicable.
     */
    public function isApplicable(): bool;

    /**
     * Returns the validation group name to be used in validation.
     */
    public function getValidationGroupName(): string;
}
