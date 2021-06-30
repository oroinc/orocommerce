<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DataProvider;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoppingListLineItemsDataProviderTest extends TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|MockObject */
    private $registry;

    /** @var ShoppingListLineItemsDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->provider = new ShoppingListLineItemsDataProvider($this->registry);
    }

    public function testGetShoppingListLineItemsWhenNotInitialized(): void
    {
        /** @var LineItem[] $lineItems */
        $lineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
        ];

        $lazyCollection = $this->createMock(AbstractLazyCollection::class);
        $lazyCollection->expects($this->once())
            ->method('isInitialized')
            ->willReturn(false);

        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects($this->any())
            ->method('getLineItems')
            ->willReturn($lazyCollection);

        $repo = $this->createMock(LineItemRepository::class);
        $repo->expects($this->once())
            ->method('getItemsWithProductByShoppingList')
            ->with($shoppingList)
            ->willReturn($lineItems);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals($lineItems, $this->provider->getShoppingListLineItems($shoppingList));
        // Second assert are using to be sure that local cache is used
        $this->assertEquals($lineItems, $this->provider->getShoppingListLineItems($shoppingList));
    }

    public function testGetShoppingListLineItemsWhenArrayCollection(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects($this->any())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection());

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->assertEquals([], $this->provider->getShoppingListLineItems($shoppingList));
        // Second assert are using to be sure that local cache is used
        $this->assertEquals([], $this->provider->getShoppingListLineItems($shoppingList));
    }

    public function testGetShoppingListLineItemsWhenEmptyPersistentCollection(): void
    {
        $lazyCollection = $this->createMock(AbstractLazyCollection::class);
        $lazyCollection->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true);
        $lazyCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects($this->any())
            ->method('getLineItems')
            ->willReturn($lazyCollection);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->assertEquals([], $this->provider->getShoppingListLineItems($shoppingList));
        // Second assert are using to be sure that local cache is used
        $this->assertEquals([], $this->provider->getShoppingListLineItems($shoppingList));
    }

    /**
     * @param LineItem[] $lineItems
     * @param Product[] $expectedProducts
     * @dataProvider productsDataProvider
     */
    public function testGetProductsWithConfigurableVariants(array $lineItems, array $expectedProducts): void
    {
        $this->assertSame(
            $expectedProducts,
            $this->provider->getProductsWithConfigurableVariants($lineItems)
        );
    }

    public function productsDataProvider(): array
    {
        $simple1 = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]);
        $simple2 = $this->getEntity(Product::class, ['id' => 2, 'type' => Product::TYPE_SIMPLE]);
        $simple3 = $this->getEntity(Product::class, ['id' => 3, 'type' => Product::TYPE_SIMPLE]);

        $variant11 = $this->getEntity(Product::class, ['id' => 11, 'type' => Product::TYPE_SIMPLE]);
        $variant12 = $this->getEntity(Product::class, ['id' => 12, 'type' => Product::TYPE_SIMPLE]);
        $variant13 = $this->getEntity(Product::class, ['id' => 13, 'type' => Product::TYPE_SIMPLE]);
        $variantLink11 = $this->getEntity(ProductVariantLink::class, ['product' => $variant11]);
        $variantLink12 = $this->getEntity(ProductVariantLink::class, ['product' => $variant12]);
        $variantLink13 = $this->getEntity(ProductVariantLink::class, ['product' => $variant13]);
        $configurable1 = $this->getEntity(Product::class, [
            'id' => 10,
            'type' => Product::TYPE_CONFIGURABLE,
            'variantLinks' => [$variantLink11, $variantLink12, $variantLink13],
        ]);
        $this->setValue($variantLink11, 'parentProduct', $configurable1);
        $this->setValue($variantLink12, 'parentProduct', $configurable1);
        $this->setValue($variantLink13, 'parentProduct', $configurable1);

        $variant21 = $this->getEntity(Product::class, ['id' => 21, 'type' => Product::TYPE_SIMPLE]);
        $variant22 = $this->getEntity(Product::class, ['id' => 22, 'type' => Product::TYPE_SIMPLE]);
        $variantLink21 = $this->getEntity(ProductVariantLink::class, ['product' => $variant21]);
        $variantLink22 = $this->getEntity(ProductVariantLink::class, ['product' => $variant22]);
        $configurable2 = $this->getEntity(Product::class, [
            'id' => 20,
            'type' => Product::TYPE_CONFIGURABLE,
            'variantLinks' => [$variantLink21, $variantLink22],
        ]);
        $this->setValue($variantLink21, 'parentProduct', $configurable2);
        $this->setValue($variantLink22, 'parentProduct', $configurable2);

        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 1, 'product' => $simple1]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 2, 'product' => $simple2]);
        $lineItem3 = $this->getEntity(LineItem::class, ['id' => 3, 'product' => $simple3]);
        $lineItem12 = $this->getEntity(
            LineItem::class,
            [
                'id' => 12,
                'product' => $variant12,
                'parentProduct' => $configurable1
            ]
        );

        $lineItem21 = $this->getEntity(
            LineItem::class,
            [
                'id' => 21,
                'product' => $variant21,
                'parentProduct' => $configurable2
            ]
        );

        return [
            'no line items' => [
                'lineItems' => [],
                'expectedProducts' => [],
            ],
            'simple products' => [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'expectedProducts' => [$simple1, $simple2, $simple3],
            ],
            'simple and configurable' => [
                'lineItems' => [$lineItem1, $lineItem12, $lineItem3],
                'expectedProducts' => [$simple1, $variant11, $variant12, $variant13, $simple3],
            ],
            'simple and two configurables' => [
                'lineItems' => [$lineItem12, $lineItem21, $lineItem3],
                'expectedProducts' => [$variant11, $variant12, $variant13, $variant21, $variant22, $simple3],
            ],
        ];
    }
}
