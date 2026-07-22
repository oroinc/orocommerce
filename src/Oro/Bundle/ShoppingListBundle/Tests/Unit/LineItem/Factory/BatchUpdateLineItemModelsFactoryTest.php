<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\LineItem\Factory;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\LineItem\Factory\BatchUpdateLineItemModelsFactory;
use Oro\Bundle\ShoppingListBundle\Model\LineItemModel;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class BatchUpdateLineItemModelsFactoryTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private LineItemRepository&MockObject $lineItemRepository;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private BatchUpdateLineItemModelsFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->lineItemRepository);

        $this->factory = new BatchUpdateLineItemModelsFactory(
            $doctrine,
            $this->authorizationChecker,
            $this->tokenAccessor
        );
        $this->setUpLoggerMock($this->factory);
    }

    public function testCreateLineItemModelsForEditableLineItem(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $lineItem = $this->getLineItem(10, $shoppingList);

        $this->lineItemRepository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [10]])
            ->willReturn([$lineItem]);
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['EDIT', $lineItem, true],
                ['EDIT', $shoppingList, true],
            ]);
        $this->assertLoggerNotCalled();

        $result = $this->factory->createLineItemModels(
            [['id' => 10, 'quantity' => 5, 'unitCode' => 'bottle']],
            $shoppingList
        );

        self::assertEquals([new LineItemModel(10, 5.0, 'bottle')], $result);
    }

    public function testCreateLineItemModelsSkipsAndLogsLineItemFromAnotherShoppingList(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $lineItem = $this->getLineItem(10, $this->getShoppingList(2));

        $this->lineItemRepository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [10]])
            ->willReturn([$lineItem]);
        // The line item does not belong to the updated shopping list, so the authorization is not even checked.
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->assertLoggerErrorMethodCalled();

        $result = $this->factory->createLineItemModels(
            [['id' => 10, 'quantity' => 5, 'unitCode' => 'bottle']],
            $shoppingList
        );

        self::assertSame([], $result);
    }

    public function testCreateLineItemModelsSkipsAndLogsLineItemWhenEditIsNotGranted(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $lineItem = $this->getLineItem(10, $shoppingList);

        $this->lineItemRepository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [10]])
            ->willReturn([$lineItem]);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('EDIT', $lineItem)
            ->willReturn(false);
        $this->assertLoggerErrorMethodCalled();

        $result = $this->factory->createLineItemModels(
            [['id' => 10, 'quantity' => 5, 'unitCode' => 'bottle']],
            $shoppingList
        );

        self::assertSame([], $result);
    }

    public function testCreateLineItemModelsSkipsLineItemWithNotPositiveQuantity(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $lineItem = $this->getLineItem(10, $shoppingList);

        $this->lineItemRepository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [10]])
            ->willReturn([$lineItem]);
        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['EDIT', $lineItem, true],
                ['EDIT', $shoppingList, true],
            ]);
        // The line item is editable, so the skip is caused by the invalid quantity and is not logged as an incident.
        $this->assertLoggerNotCalled();

        $result = $this->factory->createLineItemModels(
            [['id' => 10, 'quantity' => 0, 'unitCode' => 'bottle']],
            $shoppingList
        );

        self::assertSame([], $result);
    }

    public function testCreateLineItemModelsReturnsEmptyWhenThereAreNoValidIds(): void
    {
        $this->lineItemRepository->expects(self::never())
            ->method('findBy');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->assertLoggerNotCalled();

        $result = $this->factory->createLineItemModels(
            [['id' => 'not-a-number', 'quantity' => 5, 'unitCode' => 'bottle']],
            $this->getShoppingList(1)
        );

        self::assertSame([], $result);
    }

    private function getShoppingList(int $id): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);

        return $shoppingList;
    }

    private function getLineItem(int $id, ShoppingList $shoppingList): LineItem
    {
        $lineItem = new LineItem();
        ReflectionUtil::setId($lineItem, $id);
        $lineItem->setShoppingList($shoppingList);

        return $lineItem;
    }
}
