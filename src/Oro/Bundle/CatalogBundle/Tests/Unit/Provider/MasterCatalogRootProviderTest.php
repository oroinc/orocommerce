<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class MasterCatalogRootProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepository;

    /** @var AclHelper */
    private $aclHelper;

    /** @var MasterCatalogRootProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->provider = new MasterCatalogRootProvider($registry, $this->aclHelper);
    }

    public function testGetMasterCatalogRootForCurrentOrganization()
    {
        $category = new Category();

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects($this->once())
            ->method('getSingleResult')
            ->willReturn($category);
        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->willReturn($query);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->categoryRepository->expects($this->once())
            ->method('getMasterCatalogRootQueryBuilder')
            ->willReturn($queryBuilder);

        $this->provider->getMasterCatalogRoot();
    }
}
