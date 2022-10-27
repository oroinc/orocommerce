<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\EntityListener\LineItem;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
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

    public function testPrePersist()
    {
        $configurableLineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
            $this->getEntity(LineItem::class, ['id' => 2]),
        ];

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList
            ->addLineItem($configurableLineItems[0])
            ->addLineItem($configurableLineItems[1]);

        $unit = new ProductUnit();

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
            ->expects(static::once())
            ->method('findBy')
            ->with([
                'shoppingList' => $shoppingList,
                'unit' => $unit,
                'product' => $parentProducts[0],
            ])
            ->willReturn([$configurableLineItems[0]]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->with('OroShoppingListBundle:LineItem')
            ->willReturn($repository);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        (new RemoveParentProductsFromShoppingListLineItemListener())->prePersist($lineItem, $event);

        static::assertEquals(new ArrayCollection([1 => $configurableLineItems[1]]), $shoppingList->getLineItems());
    }
}
