<?php

namespace Oro\Bundle\ProductBundle\Storage;

/**
 * Provides common functionality for storing and retrieving product data.
 *
 * This base class implements serialization and deserialization methods for converting product data
 * to and from a storable format. Subclasses should implement specific storage mechanisms (e.g., session,
 * database, cache) while using these methods to handle data transformation.
 */
abstract class AbstractDataStorage
{
    /**
     * @param array $data
     * @return string
     */
    protected function prepareData(array $data)
    {
        return serialize($data);
    }

    /**
     * @param string $rowData
     * @return array
     *
     * Use serialize to avoid dates and models management
     */
    protected function parseData($rowData)
    {
        $data = @unserialize($rowData);

        return $data !== false && is_array($data) ? $data : [];
    }
}
