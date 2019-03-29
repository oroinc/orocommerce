<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;

/**
 * Provides master catalog root
 */
class MasterCatalogRootProvider
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var TokenAccessor
     */
    private $tokenAccessor;

    /**
     * @param CategoryRepository $categoryRepository
     * @param TokenAccessor $tokenAccessor
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        TokenAccessor $tokenAccessor
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param Organization|null $organization
     * @return Category
     */
    public function getMasterCatalogRootByOrganization(Organization $organization = null)
    {
        if ($organization === null) {
            $organization = $this->tokenAccessor->getOrganization();
        }

        return $this->categoryRepository->getMasterCatalogRoot($organization);
    }
}
