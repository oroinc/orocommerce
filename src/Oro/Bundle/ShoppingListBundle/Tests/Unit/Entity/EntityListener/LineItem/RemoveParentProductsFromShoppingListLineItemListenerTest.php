<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\EntityListener\LineItem;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ShoppingListBundle\Entity\EntityListener\LineItem\RemoveParentProductsFromShoppingListLineItemListener;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class RemoveParentProductsFromShoppingListLineItemListenerTest extends TestCase
{
    use EntityTrait;

    public function testPrePersist(): void
    {
        $configurableLineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
            $this->getEntity(LineItem::class, ['id' => 2]),
        ];

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList
            ->addLineItem($configurableLineItems[0])
            ->addLineItem($configurableLineItems[1]);

        $unit = (new ProductUnit())->setCode('item');

        $parentProducts = [
            $this->getEntity(Product::class, ['id' => 100, 'type' => Product::TYPE_CONFIGURABLE]),
            $this->getEntity(Product::class, ['id' => 200, 'type' => Product::TYPE_CONFIGURABLE]),
        ];

        $product = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]);
        $product
            ->addParentVariantLink(new ProductVariantLink($parentProducts[0], $product))
            ->addParentVariantLink(new ProductVariantLink($parentProducts[1], $product));

        $lineItem = $this->getEntity(LineItem::class, ['id' => 11]);
        $lineItem
            ->setShoppingList($shoppingList)
            ->setProduct($product)
            ->setParentProduct($parentProducts[0])
            ->setUnit($unit);

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findBy')
            ->with([
                'shoppingList' => $shoppingList->getId(),
                'unit' => $unit,
                'product' => $parentProducts[0]->getId(),
            ])
            ->willReturn([$configurableLineItems[0]]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        (new RemoveParentProductsFromShoppingListLineItemListener())->prePersist($lineItem, $event);

        self::assertEquals(new ArrayCollection([1 => $configurableLineItems[1]]), $shoppingList->getLineItems());
    }

    public function testPrePersistWhenNoParentProduct(): void
    {
        $lineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
            $this->getEntity(LineItem::class, ['id' => 2]),
        ];

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList
            ->addLineItem($lineItems[0])
            ->addLineItem($lineItems[1]);

        $unit = (new ProductUnit())->setCode('item');
        $product = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]);

        $lineItem = $this->getEntity(LineItem::class, ['id' => 11]);
        $lineItem
            ->setShoppingList($shoppingList)
            ->setProduct($product)
            ->setUnit($unit);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects(self::never())
            ->method(self::anything());

        (new RemoveParentProductsFromShoppingListLineItemListener())->prePersist($lineItem, $event);

        self::assertEquals(new ArrayCollection($lineItems), $shoppingList->getLineItems());
    }
}
