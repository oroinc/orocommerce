<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;

trait ConfigsGeneratorTrait
{
    /**
     * @param int $count
     *
     * @return PriceListConfig[]
     */
    private function createConfigs(int $count): array
    {
        $result = [];
        $reflectionClass = new \ReflectionClass(PriceList::class);

        for ($i = 1; $i <= $count; $i++) {
            $priceList = new PriceList();
            $reflectionProperty = $reflectionClass->getProperty('id');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($priceList, $i);
            $priceList->setName('Price List ' . $i);

            $config = new PriceListConfig();
            $config->setPriceList($priceList)
                ->setSortOrder($i * 100);
            $config->setMergeAllowed(true);
            if (($i % 2) === 0) {
                $config->setMergeAllowed(false);
            }

            $result[] = $config;
        }

        return $result;
    }
}
