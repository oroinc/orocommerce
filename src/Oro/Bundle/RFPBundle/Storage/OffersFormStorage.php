<?php

namespace Oro\Bundle\RFPBundle\Storage;

use Oro\Bundle\ProductBundle\Storage\AbstractDataStorage;

/**
 * Storage handler for RFP offer data serialization and deserialization in forms.
 *
 * This class extends {@see AbstractDataStorage} to manage the persistence of offer data
 * (quantity/unit/price combinations from {@see RequestProductItem} entities) during the quote or order creation
 * process from an RFP request. It handles serialization of offer arrays for storage and deserialization
 * for form population. Developers implementing custom data storage mechanisms for RFP-to-quote
 * or RFP-to-order workflows should extend this class to modify serialization behavior or add validation logic.
 */
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
