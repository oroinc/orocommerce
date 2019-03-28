<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Loads root category
 */
class LoadMasterCatalogRoot extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $title = new LocalizedFallbackValue();
        $title->setString('All Products');
        $defaultOrganization = $manager->getRepository(Organization::class)->getFirst();

        $category = new Category();
        $category->addTitle($title);
        $category->setOrganization($defaultOrganization);

        $manager->persist($category);
        $manager->flush($category);
    }
}
