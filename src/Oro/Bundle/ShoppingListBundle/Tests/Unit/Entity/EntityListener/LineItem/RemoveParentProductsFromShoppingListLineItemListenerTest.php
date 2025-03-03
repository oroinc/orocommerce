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
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class RemoveParentProductsFromShoppingListLineItemListenerTest extends TestCase
{
    private RemoveParentProductsFromShoppingListLineItemListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new RemoveParentProductsFromShoppingListLineItemListener();
    }

    private function getShoppingList(int $id, array $lineItems): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);
        foreach ($lineItems as $lineItem) {
            $shoppingList->addLineItem($lineItem);
        }

        return $shoppingList;
    }

    private function getLineItem(int $id): LineItem
    {
        $lineItem = new LineItem();
        ReflectionUtil::setId($lineItem, $id);

        return $lineItem;
    }

    private function getProduct(int $id, string $type): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);
        $product->setType($type);

        return $product;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    public function testPrePersist(): void
    {
        $lineItems = [
            $this->getLineItem(1),
            $this->getLineItem(2)
        ];
        $shoppingList = $this->getShoppingList(1, $lineItems);

        $productUnit = $this->getProductUnit('item');

        $parentProducts = [
            $this->getProduct(100, Product::TYPE_CONFIGURABLE),
            $this->getProduct(200, Product::TYPE_CONFIGURABLE)
        ];

        $product = $this->getProduct(1, Product::TYPE_SIMPLE);
        $product->addParentVariantLink(new ProductVariantLink($parentProducts[0], $product));
        $product->addParentVariantLink(new ProductVariantLink($parentProducts[1], $product));

        $lineItem = $this->getLineItem(11);
        $lineItem->setShoppingList($shoppingList);
        $lineItem->setProduct($product);
        $lineItem->setParentProduct($parentProducts[0]);
        $lineItem->setUnit($productUnit);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with([
                'shoppingList' => $shoppingList->getId(),
                'unit' => $productUnit,
                'product' => $parentProducts[0]->getId(),
            ])
            ->willReturn([$lineItems[0]]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($repository);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $this->listener->prePersist($lineItem, $event);

        self::assertEquals(new ArrayCollection([1 => $lineItems[1]]), $shoppingList->getLineItems());
    }

    public function testPrePersistWhenNoParentProduct(): void
    {
        $lineItems = [
            $this->getLineItem(1),
            $this->getLineItem(2)
        ];
        $shoppingList = $this->getShoppingList(1, $lineItems);

        $lineItem = $this->getLineItem(11);
        $lineItem->setShoppingList($shoppingList);
        $lineItem->setProduct($this->getProduct(1, Product::TYPE_SIMPLE));
        $lineItem->setUnit($this->getProductUnit('item'));

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects(self::never())
            ->method(self::anything());

        $this->listener->prePersist($lineItem, $event);

        self::assertEquals(new ArrayCollection($lineItems), $shoppingList->getLineItems());
    }
}
