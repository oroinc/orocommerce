<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\ShoppingListSubtotalGridListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoppingListSubtotalGridListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private ShoppingListTotalManager&MockObject $shoppingListTotalManager;
    private ShoppingListSubtotalGridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shoppingListTotalManager = $this->createMock(ShoppingListTotalManager::class);

        $this->listener = new ShoppingListSubtotalGridListener($this->doctrine, $this->shoppingListTotalManager);
    }

    public function testOnResultAfterWhenAllRecordsHaveSubtotal(): void
    {
        $records = [
            new ResultRecord(['id' => 1, 'subtotal' => '2000.0000', 'currency' => 'USD']),
            new ResultRecord(['id' => 2, 'subtotal' => 0, 'currency' => 'USD']),
            new ResultRecord(['id' => 3, 'subtotal' => '0.0000', 'currency' => 'USD']),
        ];

        $this->doctrine->expects(self::never())
            ->method(self::anything());
        $this->shoppingListTotalManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onResultAfter(new OrmResultAfter($this->createMock(DatagridInterface::class), $records));

        self::assertSame('2000.0000', $records[0]->getValue('subtotal'));
        self::assertSame(0, $records[1]->getValue('subtotal'));
        self::assertSame('0.0000', $records[2]->getValue('subtotal'));
    }

    public function testOnResultAfterRecalculatesRecordsWithoutSubtotal(): void
    {
        $records = [
            new ResultRecord(['id' => 1, 'subtotal' => null, 'currency' => 'USD']),
            new ResultRecord(['id' => 2, 'subtotal' => '2000.0000', 'currency' => 'USD']),
            new ResultRecord(['id' => 3, 'subtotal' => null, 'currency' => 'EUR']),
        ];

        $shoppingList1 = $this->getShoppingList(1, 'USD');
        $shoppingList3 = $this->getShoppingList(3, 'EUR');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [1, 3]])
            ->willReturn([$shoppingList1, $shoppingList3]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('flush');

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($repository);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($entityManager);

        $this->shoppingListTotalManager->expects(self::exactly(2))
            ->method('getShoppingListTotalForCurrency')
            ->willReturnMap([
                [$shoppingList1, 'USD', false, $this->getShoppingListTotal($shoppingList1, 'USD', 123.45)],
                [$shoppingList3, 'EUR', false, $this->getShoppingListTotal($shoppingList3, 'EUR', 0.0)],
            ]);

        $this->listener->onResultAfter(new OrmResultAfter($this->createMock(DatagridInterface::class), $records));

        self::assertSame(123.45, $records[0]->getValue('subtotal'));
        self::assertSame('USD', $records[0]->getValue('currency'));
        self::assertSame('2000.0000', $records[1]->getValue('subtotal'));
        self::assertSame(0.0, $records[2]->getValue('subtotal'));
        self::assertSame('EUR', $records[2]->getValue('currency'));
    }

    public function testOnResultAfterWhenShoppingListNotFound(): void
    {
        $records = [
            new ResultRecord(['id' => 5, 'subtotal' => null, 'currency' => 'USD']),
        ];

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [5]])
            ->willReturn([]);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($repository);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($this->createMock(EntityManagerInterface::class));

        $this->shoppingListTotalManager->expects(self::never())
            ->method('getShoppingListTotalForCurrency');

        $this->listener->onResultAfter(new OrmResultAfter($this->createMock(DatagridInterface::class), $records));

        self::assertNull($records[0]->getValue('subtotal'));
    }

    private function getShoppingList(int $id, string $currency): ShoppingList
    {
        $shoppingList = new ShoppingListStub();
        $shoppingList->setId($id);
        $shoppingList->setCurrency($currency);

        return $shoppingList;
    }

    private function getShoppingListTotal(
        ShoppingList $shoppingList,
        string $currency,
        float $amount
    ): ShoppingListTotal {
        $subtotal = new Subtotal();
        $subtotal->setAmount($amount);
        $subtotal->setCurrency($currency);

        $shoppingListTotal = new ShoppingListTotal($shoppingList, $currency);
        $shoppingListTotal->setSubtotal($subtotal);

        return $shoppingListTotal;
    }
}
