<?php

namespace Oro\Bundle\ProductBundle\Storage;

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
