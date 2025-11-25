<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\EntityListener\LineItem;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\EntityListener\LineItem as LineItemFolder;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RemoveParentAndVariantLineItemsFromShoppingListListenerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UnitOfWork&MockObject $uow;
    private LineItemRepository&MockObject $repo;

    private LineItemFolder\RemoveParentAndVariantLineItemsFromShoppingListListener $listener;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->repo = $this->createMock(LineItemRepository::class);

        $this->em->expects(self::any())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->repo);
        $this->em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $this->listener = new LineItemFolder\RemoveParentAndVariantLineItemsFromShoppingListListener();
    }

    public function testOnFlushNoScheduledEntities(): void
    {
        $this->em->expects(self::never())
            ->method('remove');

        $this->repo->expects(self::never())
            ->method('getParentItemsByParentProduct');

        $this->repo->expects(self::never())
            ->method('getVariantsItemsByParentProduct');

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    public function testOnFlushNoLineItemsEntities(): void
    {
        $this->em->expects(self::never())
            ->method('remove');

        $this->repo->expects(self::never())
            ->method('getParentItemsByParentProduct');

        $this->repo->expects(self::never())
            ->method('getVariantsItemsByParentProduct');

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new OrderLineItem()]);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new OrderLineItem()]);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    public function testOnFlushNoConfigurableAndNoVariants(): void
    {
        $this->em->expects(self::never())
            ->method('remove');

        $this->repo->expects(self::never())
            ->method('getParentItemsByParentProduct');

        $this->repo->expects(self::never())
            ->method('getVariantsItemsByParentProduct');

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new LineItem()]);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new LineItem()]);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    public function testOnFlush(): void
    {
        $lineItem = $this->getLineItem(new ShoppingList(), null, null, new Product());
        $lineItem2 = $this->getLineItem(null, new ShoppingList(), (new Product())->setType('configurable'));
        $lineItem3 = $this->getLineItem(null, new ShoppingList(), null, new Product());
        $lineItem4 = $this->getLineItem(new ShoppingList(), null, (new Product())->setType('configurable'));

        $lineItemReturn = $this->getLineItem(new ShoppingList());
        $lineItemReturn2 = $this->getLineItem(null, new ShoppingList());
        $lineItemReturn3 = $this->getLineItem(new ShoppingList());
        $lineItemReturn4 = $this->getLineItem(null, new ShoppingList());

        $this->em->expects(self::exactly(4))
            ->method('remove')
            ->withConsecutive([$lineItemReturn], [$lineItemReturn2], [$lineItemReturn3], [$lineItemReturn4]);

        $this->repo->expects(self::exactly(2))
            ->method('getParentItemsByParentProduct')
            ->withConsecutive([$lineItem], [$lineItem3])
            ->willReturnOnConsecutiveCalls([$lineItemReturn], [$lineItemReturn3]);

        $this->repo->expects(self::exactly(2))
            ->method('getVariantsItemsByParentProduct')
            ->withConsecutive([$lineItem2], [$lineItem4])
            ->willReturnOnConsecutiveCalls([$lineItemReturn2], [$lineItemReturn4]);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$lineItem]);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$lineItem2, $lineItem3, $lineItem4]);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    private function getLineItem(
        ?ShoppingList $shoppingList = null,
        ?ShoppingList $savedForLaterList = null,
        ?Product $product = null,
        ?Product $parentProduct = null,
    ): LineItem {
        $lineItem = new LineItem();
        if ($shoppingList) {
            $lineItem->setShoppingList($shoppingList);
        }
        if ($savedForLaterList) {
            $lineItem->setSavedForLaterList($savedForLaterList);
        }
        if ($product) {
            $lineItem->setProduct($product);
        }
        if ($parentProduct) {
            $lineItem->setParentProduct($parentProduct);
        }

        return $lineItem;
    }
}
