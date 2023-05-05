<?php

namespace Oro\Bundle\ProductBundle\Provider;

/**
 * Provides a list of available product types.
 */
class ProductTypeProvider
{
    private array $availableProductTypes;

    public function __construct(array $availableProductTypes)
    {
        $this->availableProductTypes = $availableProductTypes;
    }

    public function getAvailableProductTypes(): array
    {
        $availableProductTypesChoices = [];
        foreach ($this->availableProductTypes as $availableProductType) {
            $availableProductTypesChoices['oro.product.type.' . $availableProductType] = $availableProductType;
        }

        return $availableProductTypesChoices;
    }
}
