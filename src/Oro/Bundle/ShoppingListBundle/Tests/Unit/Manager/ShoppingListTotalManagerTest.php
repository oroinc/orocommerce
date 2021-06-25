<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Component\Testing\Unit\EntityTrait;

class ShoppingListTotalManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const USD = 'USD';
    private const EUR = 'EUR';
    private const CAD = 'CAD';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var LineItemNotPricedSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $subtotalProvider;

    /** @var ShoppingListTotalManager */
    private $totalManager;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->subtotalProvider = $this->createMock(LineItemNotPricedSubtotalProvider::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $this->currencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn(self::USD);

        $this->totalManager = new ShoppingListTotalManager(
            $this->registry,
            $this->subtotalProvider,
            $this->currencyManager
        );
    }

    public function testGetNotValidTotal()
    {
        $shoppingList1 = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $shoppingList2 = $this->getEntity(ShoppingList::class, ['id' => 2]);
        /** @var ShoppingList $shoppingList3 */
        $shoppingList3 = $this->getEntity(ShoppingList::class, ['id' => 3]);

        // Only $shoppingList3 has total record in DB
        $total = new ShoppingListTotal($shoppingList3, self::USD);
        $total->setSubtotal((new Subtotal())->setCurrency(self::USD)->setAmount(300))
            ->setValid(true);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->exactly(2))
            ->method('findBy')
            ->willReturn([$total]);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->exactly(2))
            ->method('getRepository')
            ->with(ShoppingListTotal::class)
            ->willReturn($repository);
        $em->expects($this->exactly(2))
            ->method('persist');
        $em->expects($this->once())
            ->method('flush');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->subtotalProvider->expects($this->exactly(2))
            ->method('getSubtotalByCurrency')
            ->withConsecutive(
                [$shoppingList1, self::USD],
                [$shoppingList2, self::USD]
            )
            ->willReturnOnConsecutiveCalls(
                (new Subtotal())->setCurrency(self::USD)->setAmount(100),
                (new Subtotal())->setCurrency(self::USD)->setAmount(200)
            );

        $this->totalManager->setSubtotals([$shoppingList1, $shoppingList2, $shoppingList3], false);
        $this->assertEquals(self::USD, $shoppingList1->getSubtotal()->getCurrency());
        $this->assertEquals(100, $shoppingList1->getSubtotal()->getAmount());
        $this->assertEquals(self::USD, $shoppingList2->getSubtotal()->getCurrency());
        $this->assertEquals(200, $shoppingList2->getSubtotal()->getAmount());
        $this->assertEquals(self::USD, $shoppingList3->getSubtotal()->getCurrency());
        $this->assertEquals(300, $shoppingList3->getSubtotal()->getAmount());

        // Ensures that duplicated subtotals will not be created.
        $this->totalManager->setSubtotals([$shoppingList1, $shoppingList2, $shoppingList3], true);
    }

    public function testRecalculateTotals()
    {
        $shoppingList = new ShoppingList();
        $totalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $totalEUR = new ShoppingListTotal($shoppingList, self::EUR);
        $shoppingList->addTotal($totalUSD);
        $shoppingList->addTotal($totalEUR);
        $this->currencyManager->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn([self::EUR, self::USD, self::CAD]);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $em->expects($this->once())
            ->method('persist');
        $em->expects($this->once())
            ->method('flush');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->subtotalProvider->expects($this->exactly(3))
            ->method('getSubtotalByCurrency')
            ->willReturnMap([
                [$shoppingList, self::USD, (new Subtotal())->setCurrency(self::USD)->setAmount(100)],
                [$shoppingList, self::EUR, (new Subtotal())->setCurrency(self::EUR)->setAmount(80)],
                [$shoppingList, self::CAD, (new Subtotal())->setCurrency(self::CAD)->setAmount(120)],
            ]);

        $this->totalManager->recalculateTotals($shoppingList, true);
        $this->assertSame(100.0, $totalUSD->getSubtotal()->getAmount());
        $this->assertSame(80.0, $totalEUR->getSubtotal()->getAmount());
    }

    public function testGetShoppingListTotalForCurrency(): void
    {
        $shoppingList = new ShoppingList();
        $totalUSD = (new ShoppingListTotal($shoppingList, self::USD))->setValid(true);
        $totalEUR = new ShoppingListTotal($shoppingList, self::EUR);
        $shoppingList->addTotal($totalUSD);
        $shoppingList->addTotal($totalEUR);

        $this->subtotalProvider->expects($this->never())
            ->method('getSubtotalByCurrency');

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->assertEquals(
            $totalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD)
        );
    }

    public function testGetShoppingListTotalForCurrencyWhenNoEntity(): void
    {
        $shoppingList = new ShoppingList();
        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $expectedTotalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD->setSubtotal($subtotal);
        $expectedTotalUSD->setValid(true);

        $this->subtotalProvider->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $em->expects($this->once())
            ->method('persist');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->assertEquals(
            $expectedTotalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD)
        );
        $this->assertCount(1, $shoppingList->getTotals());
        $this->assertEquals($expectedTotalUSD, $shoppingList->getTotals()->first());
    }

    public function testGetShoppingListTotalForCurrencyWhenNoEntityAndNotManagedAndDoFlush(): void
    {
        $shoppingList = new ShoppingList();
        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $expectedTotalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD->setSubtotal($subtotal);
        $expectedTotalUSD->setValid(true);

        $this->subtotalProvider->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('contains')
            ->willReturn(false);
        $em->expects($this->never())
            ->method('persist');
        $em->expects($this->never())
            ->method('flush');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->assertEquals(
            $expectedTotalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD, true)
        );
        $this->assertCount(1, $shoppingList->getTotals());
        $this->assertEquals($expectedTotalUSD, $shoppingList->getTotals()->first());
    }

    public function testGetShoppingListTotalForCurrencyWhenNotValid(): void
    {
        $shoppingList = new ShoppingList();
        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $totalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD->setSubtotal($subtotal);
        $expectedTotalUSD->setValid(true);
        $shoppingList->addTotal($totalUSD);

        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $totalUSD->setSubtotal($subtotal);

        $this->subtotalProvider->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->assertEquals(
            $expectedTotalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD)
        );
        $this->assertCount(1, $shoppingList->getTotals());
        $this->assertEquals($expectedTotalUSD, $shoppingList->getTotals()->first());
    }

    public function testGetShoppingListTotalForCurrencyWhenNotValidAndDoFlush(): void
    {
        $shoppingList = new ShoppingList();
        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $totalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD->setSubtotal($subtotal);
        $expectedTotalUSD->setValid(true);
        $shoppingList->addTotal($totalUSD);

        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $totalUSD->setSubtotal($subtotal);

        $this->subtotalProvider->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $em->expects($this->once())
            ->method('flush');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->assertEquals(
            $expectedTotalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD, true)
        );
        $this->assertCount(1, $shoppingList->getTotals());
        $this->assertEquals($expectedTotalUSD, $shoppingList->getTotals()->first());
    }
}
