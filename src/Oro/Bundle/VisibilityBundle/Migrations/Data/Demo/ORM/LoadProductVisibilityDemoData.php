<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
        parent::load($manager);

        $this->container->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
    }

    /**
     * @param ObjectManager $manager
     * @param array $row
     * @return Website
     */
    protected function getWebsite(ObjectManager $manager, array $row)
    {
        return $this->container->get('oro_website.manager')->getDefaultWebsite();
    }

    /**
     * @return string
     */
    protected function getDataFile()
    {
        return '@OroVisibilityBundle/Migrations/Data/Demo/ORM/data/products-visibility.csv';
    }
}
