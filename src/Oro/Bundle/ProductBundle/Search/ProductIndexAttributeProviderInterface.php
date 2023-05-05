<?php

namespace Oro\Bundle\ProductBundle\Search;

/**
 * Interface for index attribute provider used to manually add fields to search index
 */
interface ProductIndexAttributeProviderInterface
{
    /**
     * Add field to the list of force indexed attributes
     *
     * @param string $field
     * @return void
     */
    public function addForceIndexed(string $field) : void;

    /**
     * Check if field is presented in the list of force indexed attributes
     *
     * @param string $field
     * @return bool
     */
    public function isForceIndexed(string $field) : bool;
}
