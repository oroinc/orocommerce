<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\EventListener\ORM\OrganizationPersistListener;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * Loads root category for all organizations if it does not exist still (eg. in case of update from older version
 * when several organizations already created)
 */
class LoadMasterCatalogRootForAllOrganizations extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $allOrganizations = $manager->getRepository(Organization::class)->findAll();

        $categoriesToFlush = [];
        foreach ($allOrganizations as $organization) {
            //Create master catalog if there is no still for certain organization
            try {
                $this->getCategory($manager, $organization);
            } catch (NoResultException $exception) {
                $title = new CategoryTitle();
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

    /**
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCategory(ObjectManager $manager, OrganizationInterface $organization): void
    {
        $categoryRepository = $manager->getRepository(Category::class);
        $queryBuilder = $categoryRepository->getMasterCatalogRootQueryBuilder();
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        $queryBuilder->getQuery()->getSingleResult();
    }
}
