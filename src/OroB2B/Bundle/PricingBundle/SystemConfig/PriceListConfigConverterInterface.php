<?php

namespace OroB2B\Bundle\PricingBundle\SystemConfig;

interface PriceListConfigConverterInterface
{
    /**
     * @param PriceListConfigBag $configBag
     * @return array;
     */
    public function convertBeforeSave(PriceListConfigBag $configBag);

    /**
     * @param array $config
     * @return PriceListConfigBag
     */
    public function convertFromSaved(array $config);
}
