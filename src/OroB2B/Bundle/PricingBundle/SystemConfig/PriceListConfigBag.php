<?php

namespace OroB2B\Bundle\PricingBundle\SystemConfig;

use Doctrine\Common\Collections\ArrayCollection;

class PriceListConfigBag
{
    const CLASS_NAME = 'OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigBag';

    /**
     * @var ArrayCollection
     */
    private $configs;

    /**
     * PriceListConfigBag constructor.
     */
    public function __construct()
    {
        $this->configs = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|PriceListConfig[]
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * @param ArrayCollection $configs
     */
    public function setConfigs(ArrayCollection $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @param $config
     * @return $this
     */
    public function addConfig(PriceListConfig $config)
    {
        $this->configs->add($config);

        return $this;
    }
}
