<?php

namespace Oro\Bundle\CatalogBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

/**
 * Create a reference for root category.
 */
class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
    {
        $repository = $doctrine->getRepository(Category::class);

        $referenceRepository->set('root_category', $repository->findOneBy([]));
    }
}
