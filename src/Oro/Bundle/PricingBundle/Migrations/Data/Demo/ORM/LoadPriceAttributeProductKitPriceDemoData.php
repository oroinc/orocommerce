<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductKitDemoData;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads MSRP and MAP price attributes demo data for product kits
 */
class LoadPriceAttributeProductKitPriceDemoData extends LoadPriceAttributeProductPriceDemoData
{
    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [LoadProductKitDemoData::class]);
    }

    #[\Override]
    protected function getProducts(): \Iterator
    {
        $filePath = $this->getFileLocator()
            ->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/product_kits.yaml');
        if (\is_array($filePath)) {
            $filePath = current($filePath);
        }

        return new \ArrayIterator(Yaml::parseFile($filePath));
    }
}
