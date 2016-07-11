<?php

namespace OroB2B\Bundle\WarehouseBundle\Formatter;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class ProductUnitFormatter extends ProductUnitLabelFormatter
{
    /**
     * @param $gridName
     * @param $keyName
     * @param array $node
     * @return bool|string
     */
    public function getProductUnitLabel($gridName, $keyName, array $node = [])
    {
        if (!array_key_exists('code', $node)) {
            return false;
        }

        $code = $node['code'];

        $isPlural = false;
        if (!array_key_exists('quantity', $node)) {
            $isPlural = $node['quantity'] > 1;
        }

        return function (ResultRecordInterface $record) use ($code, $isPlural) {
            return $this->format($code, false, $isPlural);
        };
    }
}
