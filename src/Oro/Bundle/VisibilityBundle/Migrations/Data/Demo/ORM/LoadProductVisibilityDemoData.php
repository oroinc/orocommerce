<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Load product visibilities demo data.
 */
class LoadProductVisibilityDemoData extends AbstractLoadProductVisibilityDemoData
{
    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [LoadCategoryVisibilityDemoData::class]);
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        $this->container->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
    }

    #[\Override]
    protected function getWebsite(ObjectManager $manager, array $row): Website
    {
        return $this->container->get('oro_website.manager')->getDefaultWebsite();
    }

    #[\Override]
    protected function getDataFile(): string
    {
        return '@OroVisibilityBundle/Migrations/Data/Demo/ORM/data/products-visibility.csv';
    }
}
