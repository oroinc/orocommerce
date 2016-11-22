<?php

namespace Oro\Bundle\PricingBundle\Datagrid\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;

interface CombinedProductPriceProviderInterface
{
    /**
     * @param ResultRecordInterface[] $productRecords
     * @param CombinedPriceList       $priceList
     * @param string                  $currency
     * @return array
     */
    public function getCombinedPricesForProductsByPriceList(
        array $productRecords,
        CombinedPriceList $priceList,
        $currency
    );
}
