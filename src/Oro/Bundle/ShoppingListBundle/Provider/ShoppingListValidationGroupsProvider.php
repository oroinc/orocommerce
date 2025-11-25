<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Provider;

/**
 * Provides validation groups for shopping lists based on applicable validation group resolvers.
 */
class ShoppingListValidationGroupsProvider
{
    /**
     * @param iterable<ShoppingListValidationGroupResolverInterface> $validationGroupResolvers
     */
    public function __construct(private readonly iterable $validationGroupResolvers)
    {
    }

    /**
     * Returns all applicable validation group names.
     *
     * @return list<string>
     */
    public function getAllValidationGroups(): array
    {
        $groups = [];
        foreach ($this->validationGroupResolvers as $validationGroupResolver) {
            if ($validationGroupResolver->isApplicable()) {
                $groups[] = $validationGroupResolver->getValidationGroupName();
            }
        }

        return $groups;
    }

    /**
     * Returns validation group name for the given type if applicable.
     *
     * @throws \InvalidArgumentException If validation group type is not found or not applicable
     */
    public function getValidationGroupByType(string $validationGroupType): string
    {
        foreach ($this->validationGroupResolvers as $validationGroupResolver) {
            if ($validationGroupResolver->getType() === $validationGroupType) {
                if (!$validationGroupResolver->isApplicable()) {
                    continue;
                }

                return $validationGroupResolver->getValidationGroupName();
            }
        }

        throw new \InvalidArgumentException(
            \sprintf('Invalid validation group type: %s', $validationGroupType)
        );
    }
}
