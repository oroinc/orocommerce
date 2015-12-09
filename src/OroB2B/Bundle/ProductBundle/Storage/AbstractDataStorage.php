<?php

namespace OroB2B\Bundle\ProductBundle\Storage;

abstract class AbstractDataStorage
{
    /**
     * @param array $data
     * @return string
     */
    protected function prepareData(array $data)
    {
        return json_encode($data);
    }

    /**
     * @param string $rowData
     * @return array
     */
    protected function parseData($rowData)
    {
        return (array)json_decode($rowData, true);
    }
}
