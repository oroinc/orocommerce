<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

/**
 * The autocomplete handler to search brands.
 */
class BrandSearchHandler extends SearchHandler
{
    private EntityNameResolver $entityNameResolver;

    public function __construct($entityName, array $properties, EntityNameResolver $entityNameResolver)
    {
        parent::__construct($entityName, $properties);
        $this->entityNameResolver = $entityNameResolver;
    }

    #[\Override]
    public function convertItem($item)
    {
        $result = [];

        if ($this->idFieldName) {
            $result[$this->idFieldName] = $this->getPropertyValue($this->idFieldName, $item);
        }

        foreach ($this->getProperties() as $property) {
            if ($property === 'name') {
                $result[$property] = $this->entityNameResolver->getName($item);
            } else {
                $result[$property] = (string)$this->getPropertyValue($property, $item);
            }
        }

        return $result;
    }
}
