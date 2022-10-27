<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;

trait CatalogTrait
{
    protected function findCategory(string $title): ?Category
    {
        $aclHelper = self::getContainer()->get('oro_security.acl_helper');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = self::getContainer()->get('doctrine')->getRepository(Category::class);
        $query = $aclHelper->apply($categoryRepository->findOneByDefaultTitleQueryBuilder($title));

        return $query->getOneOrNullResult();
    }

    protected function getRootCategory(): Category
    {
        /** @var MasterCatalogRootProviderInterface $masterCatalogRootProvider */
        $masterCatalogRootProvider = self::getContainer()->get('oro_catalog.provider.master_catalog_root');

        return  $masterCatalogRootProvider->getMasterCatalogRoot();
    }
}
