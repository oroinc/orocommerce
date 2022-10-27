<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Delegates the getting of a default product unit precision to child providers.
 */
class ChainDefaultProductUnitProvider implements DefaultProductUnitProviderInterface
{
    /** @var iterable|DefaultProductUnitProviderInterface[] */
    private $providers;

    /**
     * @param iterable|DefaultProductUnitProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
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
