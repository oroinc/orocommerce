<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductListLowInventoryListener;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\Testing\ReflectionUtil;

class ProductListLowInventoryListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LowInventoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lowInventoryProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ProductListLowInventoryListener */
    private $listener;

    protected function setUp(): void
    {
        $this->lowInventoryProvider = $this->createMock(LowInventoryProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new ProductListLowInventoryListener(
            $this->lowInventoryProvider,
            $this->doctrine
        );
    }

    public function testOnBuildQuery(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $query->expects(self::once())
            ->method('addSelect')
            ->with('decimal.low_inventory_threshold')
            ->willReturnSelf();

        $this->listener->onBuildQuery(new BuildQueryProductListEvent('test_list', $query));
    }

    public function testOnBuildResult(): void
    {
        $productData = [
            1 => [
                'unit'                    => 'items',
                'low_inventory_threshold' => 5
            ],
            2 => [
                'unit'                    => 'items',
                'low_inventory_threshold' => ''
            ]
        ];
        $productView1 = $this->createMock(ProductView::class);
        $productView2 = $this->createMock(ProductView::class);
        $productViews = [1 => $productView1, 2 => $productView2];

        $product1 = new Product();
        ReflectionUtil::setId($product1, 1);
        $product2 = new Product();
        ReflectionUtil::setId($product2, 2);
        $productUnit = new ProductUnit();
        $productUnit->setCode('items');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
        $em->expects(self::exactly(4))
            ->method('getReference')
            ->willReturnCallback(function ($entityClass, $entityId) use ($product1, $product2, $productUnit) {
                if (Product::class === $entityClass && 1 === $entityId) {
                    return $product1;
                }
                if (Product::class === $entityClass && 2 === $entityId) {
                    return $product2;
                }
                if (ProductUnit::class === $entityClass) {
                    return $productUnit;
                }
                throw new \InvalidArgumentException(sprintf(
                    'Unexpected entity. Class: %s, ID: %s.',
                    $entityClass,
                    $entityId
                ));
            });

        $this->lowInventoryProvider->expects(self::once())
            ->method('isLowInventoryCollection')
            ->with(
                [
                    [
                        'product'                 => $product1,
                        'product_unit'            => $productUnit,
                        'low_inventory_threshold' => 5,
                        'highlight_low_inventory' => true
                    ],
                    [
                        'product'                 => $product2,
                        'product_unit'            => $productUnit,
                        'low_inventory_threshold' => -1,
                        'highlight_low_inventory' => false
                    ]
                ]
            )
            ->willReturn([1 => true]);

        $productView1->expects(self::once())
            ->method('set')
            ->with('low_inventory', self::identicalTo(true));
        $productView2->expects(self::once())
            ->method('set')
            ->with('low_inventory', self::identicalTo(false));

        $this->listener->onBuildResult(new BuildResultProductListEvent('test_list', $productData, $productViews));
    }
}
