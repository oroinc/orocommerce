<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Action;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Action\RemoveRestrictedProducts;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductHolderStub;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveRestrictedProductsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var RemoveRestrictedProducts */
    private $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->query = $this->getMockForAbstractClass(
            AbstractQuery::class,
            [],
            '',
            false,
            false,
            true,
            ['getArrayResult']
        );
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->repository = $this->createMock(ProductRepository::class);
        $this->repository->expects($this->any())
            ->method('getProductsQueryBuilder')
            ->willReturn($queryBuilder);

        $this->productManager = $this->createMock(ProductManager::class);
        $this->action = new RemoveRestrictedProducts($this->repository, $this->productManager, new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testOnlyAllowedProductsRemainsInProductHolders()
    {
        $productHolderOne = new ProductHolderStub($this->getEntity(Product::class, ['id' => 1]));
        $productHolderTwo = new ProductHolderStub($this->getEntity(Product::class, ['id' => 2]));
        $productsHolders = [$productHolderOne, $productHolderTwo];

        $options = [
            RemoveRestrictedProducts::OPTION_KEY_PRODUCT_HOLDER => new PropertyPath('path.products'),
            RemoveRestrictedProducts::OPTION_KEY_ATTRIBUTE => new PropertyPath('path.attribute'),
        ];
        $this->action->initialize($options);

        $this->query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([['id' => 1]]);

        $context = new \ArrayObject(['path' => ['products' => $productsHolders]]);
        $expectedContext = new \ArrayObject([
            'path' => ['products' => [$productHolderOne], 'attribute' => [$productHolderTwo->getProduct()]]
        ]);
        $this->action->execute($context);
        $this->assertEquals($expectedContext, $context);
    }
}
