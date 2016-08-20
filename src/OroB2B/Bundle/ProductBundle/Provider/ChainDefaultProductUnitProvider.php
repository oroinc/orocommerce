<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ChainDefaultProductUnitProvider implements DefaultProductUnitProviderInterface
{
    /**
     * @var DefaultProductUnitProviderInterface[]
     */
    protected $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param DefaultProductUnitProviderInterface $provider
     */
    public function addProvider(DefaultProductUnitProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @return ProductUnitPrecision|null
     */
    public function getDefaultProductUnitPrecision()
    {
        foreach ($this->providers as $provider) {
            $defaultPrecision = $provider->getDefaultProductUnitPrecision();
            if ($defaultPrecision) {
                return $defaultPrecision;
            }
        }
        return null;
    }
}
