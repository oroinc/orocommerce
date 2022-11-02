<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Action\Condition;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Action\Condition\AtLeastOneAvailableProduct;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductHolderStub;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyPath;

class AtLeastOneAvailableProductTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var AtLeastOneAvailableProduct */
    private $condition;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->repository = $this->createMock(ProductRepository::class);
        $this->repository->expects($this->any())
            ->method('getProductsQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->productManager = $this->createMock(ProductManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->condition = new AtLeastOneAvailableProduct($this->repository, $this->productManager, $this->aclHelper);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testConditionIsNotAllowedInCaseWrongPathToProductsProvided()
    {
        $options = [new PropertyPath('test.value')];
        $this->condition->initialize($options);

        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testConditionIsNotAllowedWhenInvalidTypeOfProductsProvided()
    {
        $options = [new PropertyPath('path.products')];
        $this->condition->initialize($options);

        $this->assertFalse($this->condition->evaluate(['path' => ['products' => null]]));
    }

    public function testConditionIsNotAllowedWhenThereIsNoProductsInContext()
    {
        $options = [new PropertyPath('path.products')];
        $this->condition->initialize($options);

        $this->assertFalse($this->condition->evaluate(['path' => ['products' => []]]));
    }

    public function testConditionIsNotAllowedWhenAllProductsAreRestricted()
    {
        $productsHolders = [
            new ProductHolderStub($this->getEntity(Product::class, ['id' => 1])),
            new ProductHolderStub($this->getEntity(Product::class, ['id' => 2]))
        ];

        $options = [new PropertyPath('path.products')];
        $this->condition->initialize($options);

        $this->queryBuilder
            ->expects($this->once())
            ->method('resetDQLPart')
            ->with('select')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('p.id')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with('1')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([]);
        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($this->queryBuilder)
            ->willReturn($query);

        $this->assertFalse($this->condition->evaluate(['path' => ['products' => $productsHolders]]));
    }

    public function testConditionIsAllowedWhenAtLeastOneProductIsNotRestricted()
    {
        $productHolderOne = new ProductHolderStub($this->getEntity(Product::class, ['id' => 1]));
        $productHolderTwo = new ProductHolderStub($this->getEntity(Product::class, ['id' => 2]));
        $productsHolders = [$productHolderOne, $productHolderTwo];

        $options = [new PropertyPath('path.products')];
        $this->condition->initialize($options);

        $this->queryBuilder
            ->expects($this->once())
            ->method('resetDQLPart')
            ->with('select')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('p.id')
            ->willReturnSelf();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with('1')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn(['id' => 1]);
        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($this->queryBuilder)
            ->willReturn($query);

        $this->assertTrue($this->condition->evaluate(['path' => ['products' => $productsHolders]]));
    }
}
