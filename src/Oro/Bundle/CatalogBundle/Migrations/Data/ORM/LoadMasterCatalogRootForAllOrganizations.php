<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\ORM\OrganizationPersistListener;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Loads root category for all organizations if it does not exist still (eg. in case of update from older version
 * when several organizations already created)
 */
class LoadMasterCatalogRootForAllOrganizations extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $allOrganizations = $manager->getRepository(Organization::class)->findAll();

        $categoriesToFlush = [];
        foreach ($allOrganizations as $organization) {
            //Create master catalog if there is no still for certain organization
            try {
                $manager->getRepository(Category::class)->getMasterCatalogRoot($organization);
            } catch (NoResultException $exception) {
                $title = new LocalizedFallbackValue();
                $title->setString(OrganizationPersistListener::ROOT_CATEGORY_NAME);

                $category = new Category();
                $category->addTitle($title);
                $category->setOrganization($organization);

                $manager->persist($category);
                $categoriesToFlush[] = $category;
            }
        }

        if ($categoriesToFlush) {
            $manager->flush($categoriesToFlush);
        }
    }
}
