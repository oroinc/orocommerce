<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\Yaml\Yaml;

/**
 * Demo fixture for loading demo product kit images cache.
 */
class LoadProductKitImagesCacheDemoData extends LoadProductImagesCacheDemoData
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [
            LoadProductKitDemoData::class,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getProducts(): \Iterator
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/product_kits.yaml');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        return new \ArrayIterator(Yaml::parseFile($filePath));
    }
}
