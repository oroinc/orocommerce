<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides master catalog root
 */
class MasterCatalogRootProvider implements MasterCatalogRootProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    public function __construct(ManagerRegistry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    public function getMasterCatalogRoot(): Category
    {
        $categoryRepository = $this->registry->getManagerForClass(Category::class)->getRepository(Category::class);
        $queryBuilder = $this->aclHelper->apply($categoryRepository->getMasterCatalogRootQueryBuilder());

        return $queryBuilder->getSingleResult();
    }
}
