<?php

namespace OroB2B\Bundle\RFPBundle\Storage;

use OroB2B\Bundle\ProductBundle\Storage\AbstractDataStorage;

class OffersFormStorage extends AbstractDataStorage
{
    const DATA_KEY = 'offers_data';

    /**
     * @param array $data
     * @return array
     */
    public function getData(array $data)
    {
        if (!array_key_exists(self::DATA_KEY, $data)) {
            return [];
        }

        return $this->parseData($data[self::DATA_KEY]);
    }

    /**
     * @param array $data
     * @return string
     */
    public function getRawData(array $data)
    {
        return $this->prepareData($data);
    }
}
