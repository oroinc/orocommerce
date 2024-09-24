<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductKitDemoData;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads sort order demo data for ProductKitCollection ContentVariants in WebCatalog
 */
class LoadSortOrderForProductKitsDemoData extends LoadSortOrderForProductCollectionsContentVariantsDemoData
{
    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [
            LoadProductKitDemoData::class,
        ]);
    }

    #[\Override]
    protected function getAllProductData(): array
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/product_kits.yaml');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        return Yaml::parseFile($filePath);
    }
}
