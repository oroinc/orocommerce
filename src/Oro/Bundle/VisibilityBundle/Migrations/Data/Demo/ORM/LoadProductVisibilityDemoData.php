<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;

class LoadProductVisibilityDemoData extends AbstractLoadProductVisibilityDemoData
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(parent::getDependencies(), [LoadCategoryVisibilityDemoData::class]);
    }
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->resetVisibilities($manager);
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroVisibilityBundle/Migrations/Data/Demo/ORM/data/products-visibility.csv');
        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $product = $this->getProduct($manager, $row['product']);
            $visibility = $row['visibility'];
            $this->setProductVisibility($manager, $row, $product, $visibility);
        }
        fclose($handler);
        $manager->flush();
        $this->container->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
    }
}
