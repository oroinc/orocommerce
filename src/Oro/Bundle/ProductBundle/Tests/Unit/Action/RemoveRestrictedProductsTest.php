<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Action;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Action\RemoveRestrictedProducts;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductHolderStub;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveRestrictedProductsTest extends \PHPUnit\Framework\TestCase
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

    /** @var RemoveRestrictedProducts */
    private $action;

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
        $this->action = new RemoveRestrictedProducts(
            $this->repository,
            $this->productManager,
            $this->aclHelper,
            new ContextAccessor()
        );
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

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([['id' => 1]]);
        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($this->queryBuilder)
            ->willReturn($query);

        $context = new \ArrayObject(['path' => ['products' => $productsHolders]]);
        $expectedContext = new \ArrayObject([
            'path' => ['products' => [$productHolderOne], 'attribute' => [$productHolderTwo->getProduct()]]
        ]);
        $this->action->execute($context);
        $this->assertEquals($expectedContext, $context);
    }
}
