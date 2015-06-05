<?php

namespace OroB2B\Bundle\ProductBundle\Model;

class InventoryStatus
{
    const IN_STOCK = 'in_stock';
    const OUT_OF_STOCK   = 'out_of_stock';
    const DISCONTINUED = 'discontinued';

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [self::IN_STOCK, self::OUT_OF_STOCK, self::DISCONTINUED];
    }
}
