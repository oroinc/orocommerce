<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Action\Condition;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Action\Condition\AtLeastOneAvailableProduct;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductHolderStub;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyPath;

class AtLeastOneAvailableProductTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var AtLeastOneAvailableProduct */
    private $condition;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->query = $this->getMockForAbstractClass(AbstractQuery::class, [], '', false, false, true, ['getResult']);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->repository = $this->createMock(ProductRepository::class);
        $this->repository->expects($this->any())
            ->method('getProductsQueryBuilder')
            ->willReturn($queryBuilder);

        $this->productManager = $this->createMock(ProductManager::class);
        $this->condition = new AtLeastOneAvailableProduct($this->repository, $this->productManager);
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

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->assertFalse($this->condition->evaluate(['path' => ['products' => $productsHolders]]));
    }

    public function testConditionIsAllowedWhenAtLeastOneProductIsNotRestricted()
    {
        $productHolderOne = new ProductHolderStub($this->getEntity(Product::class, ['id' => 1]));
        $productHolderTwo = new ProductHolderStub($this->getEntity(Product::class, ['id' => 2]));
        $productsHolders = [$productHolderOne, $productHolderTwo];

        $options = [new PropertyPath('path.products')];
        $this->condition->initialize($options);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([$productHolderOne]);

        $this->assertTrue($this->condition->evaluate(['path' => ['products' => $productsHolders]]));
    }
}
