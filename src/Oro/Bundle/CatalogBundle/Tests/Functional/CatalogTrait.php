<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;

trait CatalogTrait
{
    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function findCategory(string $title): ?Category
    {
        $aclHelper = $this->getContainer()->get('oro_security.acl_helper');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->getContainer()->get('doctrine')->getRepository(Category::class);
        $query = $aclHelper->apply($categoryRepository->findOneByDefaultTitleQueryBuilder($title));

        return $query->getOneOrNullResult();
    }

    protected function getRootCategory(): Category
    {
        /** @var MasterCatalogRootProviderInterface $masterCatalogRootProvider */
        $masterCatalogRootProvider = $this->getContainer()->get('oro_catalog.provider.master_catalog_root');

        return  $masterCatalogRootProvider->getMasterCatalogRoot();
    }
}
