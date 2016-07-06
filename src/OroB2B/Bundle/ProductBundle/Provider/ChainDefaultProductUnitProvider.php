<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ChainDefaultProductUnitProvider
{
    /** @var AbstractDefaultProductUnitProvider[] */
    protected $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param AbstractDefaultProductUnitProvider $provider
     */
    public function addProvider(AbstractDefaultProductUnitProvider $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @return ProductUnitPrecision|null
     * @throws \Exception
     */
    public function getDefaultProductUnitPrecision()
    {
        foreach ($this->providers as $provider) {
            if ($provider instanceof AbstractDefaultProductUnitProvider) {
                $defaultPrecision = $provider->getDefaultProductUnitPrecision();
                if ($defaultPrecision) {
                    return $defaultPrecision;
                }
            } else {
                throw new \Exception('Any DefaultProductUnitProvider should extend AbstractDefaultProductUnitProvider');
            }
        }

        return null;
    }
}
