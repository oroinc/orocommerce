<?php

namespace OroB2B\Bundle\PricingBundle\SystemConfig;

interface PriceListConfigConverterInterface
{
    /**
     * @param array $configs
     * @return array ;
     */
    public function convertBeforeSave(array $configs);

    /**
     * @param array $config
     * @return PriceListConfig
     */
    public function convertFromSaved(array $config);
}
