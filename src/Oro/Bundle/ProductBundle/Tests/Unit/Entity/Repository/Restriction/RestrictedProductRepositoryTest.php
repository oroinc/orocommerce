<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Repository\Restriction;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\Restriction\RestrictedProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class RestrictedProductRepositoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProductManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productManager;

    /**
     * @var AclHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $aclHelper;

    /**
     * @var RestrictedProductRepository
     */
    private $restrictedProductRepository;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->restrictedProductRepository = new RestrictedProductRepository(
            $this->doctrineHelper,
            $this->productManager,
            $this->aclHelper,
            Product::class
        );
    }

    public function testFindProducts()
    {
        $productIds = [1, 2, 3];
        $expected = [$this->getEntity(Product::class, ['id' => 1])];

        $repository = $this->createMock(ProductRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $qb = $this->createMock(QueryBuilder::class);

        $repository->expects($this->once())
            ->method('getProductsQueryBuilder')
            ->with($productIds)
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.id');

        $this->productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($qb, [])
            ->willReturn($qb);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expected);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $actual = $this->restrictedProductRepository->findProducts($productIds);

        $this->assertEquals($expected, $actual);
    }
}
