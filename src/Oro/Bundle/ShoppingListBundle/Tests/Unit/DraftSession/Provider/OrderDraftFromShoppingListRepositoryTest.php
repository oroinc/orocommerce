<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DraftSession\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShoppingListBundle\DraftSession\Provider\OrderDraftFromShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use PHPUnit\Framework\TestCase;

final class OrderDraftFromShoppingListRepositoryTest extends TestCase
{
    private OrderDraftFromShoppingListRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = new OrderDraftFromShoppingListRepository();
    }

    public function testSupportsWhenShoppingList(): void
    {
        self::assertTrue($this->repository->supports(ShoppingList::class));
    }

    public function testSupportsWhenNotShoppingList(): void
    {
        self::assertFalse($this->repository->supports(Order::class));
    }

    public function testHasEntityDraftAlwaysReturnsFalse(): void
    {
        $entity = $this->createMock(EntityDraftAwareInterface::class);

        self::assertFalse($this->repository->hasEntityDraft($entity, 'draft-session-uuid'));
    }

    public function testFindEntityDraftAlwaysReturnsNull(): void
    {
        $entity = $this->createMock(EntityDraftAwareInterface::class);

        self::assertNull($this->repository->findEntityDraft($entity, 'draft-session-uuid'));
    }
}
