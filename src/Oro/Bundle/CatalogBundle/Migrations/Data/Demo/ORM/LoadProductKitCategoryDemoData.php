<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductKitDemoData;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads product kit categories demo data.
 */
class LoadProductKitCategoryDemoData extends LoadProductCategoryDemoData
{
    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [
            LoadProductKitDemoData::class,
        ]);
    }

    #[\Override]
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
