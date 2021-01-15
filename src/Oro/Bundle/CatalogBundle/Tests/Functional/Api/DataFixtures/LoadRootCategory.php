<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * Loads a root category from the database.
 */
class LoadRootCategory extends AbstractFixture implements
    DependentFixtureInterface,
    InitialFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrganization::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var CategoryRepository $repository */
        $repository = $manager->getRepository(Category::class);
        $queryBuilder = $repository->getMasterCatalogRootQueryBuilder();
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $this->getReference('organization'));

        $this->addReference('root_category', $queryBuilder->getQuery()->getSingleResult());
    }
}
