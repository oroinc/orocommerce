<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Loads root category for default organization if it does not exist still (eg. in case update from crm)
 */
class LoadMasterCatalogRoot extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $defaultOrganization = $manager->getRepository(Organization::class)->getFirst();

        //Create master catalog if there is no one still
        try {
            $manager->getRepository(Category::class)->getMasterCatalogRoot($defaultOrganization);
        } catch (NoResultException $exception) {
            $title = new LocalizedFallbackValue();
            $title->setString('All Products');

            $category = new Category();
            $category->addTitle($title);
            $category->setOrganization($defaultOrganization);

            $manager->persist($category);
            $manager->flush($category);
        }
    }
}
