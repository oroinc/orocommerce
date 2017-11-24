<?php

namespace Oro\Bundle\ShoppingList\Tests\Unit\Entity\EntityListener\LineItem;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ShoppingListBundle\Entity\EntityListener\LineItem\RemoveParentProductsFromShoppingListLineItemListener;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\TestCase;

class RemoveParentProductsFromShoppingListLineItemListenerTest extends TestCase
{
    public function testPrePersist()
    {
        $configurableLineItems = [
            new LineItem(),
            new LineItem(),
        ];

        $shoppingList = new ShoppingList();
        $shoppingList
            ->addLineItem($configurableLineItems[0])
            ->addLineItem($configurableLineItems[1]);

        $unit = new ProductUnit();

        $parentProducts = [
            new Product(),
            new Product(),
        ];

        $product = new Product();
        $product
            ->addParentVariantLink(new ProductVariantLink($parentProducts[0], $product))
            ->addParentVariantLink(new ProductVariantLink($parentProducts[1], $product));

        $lineItem = new LineItem();
        $lineItem
            ->setShoppingList($shoppingList)
            ->setProduct($product)
            ->setUnit($unit);

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::once())
            ->method('findBy')
            ->with([
                'shoppingList' => $shoppingList,
                'unit' => $unit,
                'product' => $parentProducts,
            ])
            ->willReturn($configurableLineItems);

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

        static::assertEmpty($shoppingList->getLineItems());
    }
}
