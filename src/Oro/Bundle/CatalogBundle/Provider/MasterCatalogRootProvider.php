<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;

/**
 * Provides master catalog root
 */
class MasterCatalogRootProvider
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var TokenAccessor
     */
    private $tokenAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param TokenAccessor $tokenAccessor
     */
    public function __construct(ManagerRegistry $registry, TokenAccessor $tokenAccessor)
    {
        $this->registry = $registry;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @return Category
     */
    public function getMasterCatalogRootForCurrentOrganization()
    {
        $organization = $this->tokenAccessor->getOrganization();

        return $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->getMasterCatalogRoot($organization);
    }
}
