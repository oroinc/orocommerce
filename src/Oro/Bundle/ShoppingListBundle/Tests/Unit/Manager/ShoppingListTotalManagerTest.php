<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
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

    /** @var CustomerUserProvider */
    private $customerUserProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->subtotalProvider = $this->createMock(LineItemNotPricedSubtotalProvider::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->customerUserProvider = $this->createMock(CustomerUserProvider::class);

        $this->currencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn(self::USD);

        $this->totalManager = new ShoppingListTotalManager(
            $this->registry,
            $this->subtotalProvider,
            $this->currencyManager,
            $this->customerUserProvider
        );
    }

    public function testGetNotValidTotal()
    {
        # Default customer user.
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $shoppingList1 = $this->getEntity(ShoppingList::class, ['id' => 1, 'customerUser' => $customerUser]);
        $shoppingList2 = $this->getEntity(ShoppingList::class, ['id' => 2, 'customerUser' => $customerUser]);
        $shoppingList3 = $this->getEntity(ShoppingList::class, ['id' => 3, 'customerUser' => $customerUser]);

        $this->customerUserProvider
            ->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($customerUser);

        // Only $shoppingList3 has total record in DB
        $total = new ShoppingListTotal($shoppingList3, self::USD);
        $total
            ->setSubtotal((new Subtotal())->setCurrency(self::USD)->setAmount(300))
            ->setValid(true);
        $shoppingList3->addTotal($total);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->exactly(2))
            ->method('persist');
        $em
            ->expects($this->once())
            ->method('flush');

        $this->registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->subtotalProvider
            ->expects($this->exactly(2))
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
        $this->totalManager->setSubtotals([$shoppingList1, $shoppingList2, $shoppingList3], true, $customerUser);
    }

    public function testGetWithDifferenceUsers(): void
    {
        $defaultCustomerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 2]);

        $shoppingList1 = $this->getEntity(ShoppingList::class, ['id' => 1, 'customerUser' => $defaultCustomerUser]);
        $shoppingList2 = $this->getEntity(ShoppingList::class, ['id' => 2, 'customerUser' => $defaultCustomerUser]);

        $this->customerUserProvider
            ->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($customerUser);

        $default = (new ShoppingListTotal($shoppingList1, self::USD))
            ->setSubtotal((new Subtotal())->setAmount(100)->setCurrency(self::USD))
            ->setCustomerUser($defaultCustomerUser)
            ->setValid(true);
        $total1 = (new ShoppingListTotal($shoppingList1, self::USD))
            ->setSubtotal((new Subtotal())->setAmount(200)->setCurrency(self::USD))
            ->setCustomerUser($customerUser)
            ->setValid(true);
        $total2 = (new ShoppingListTotal($shoppingList1, self::USD))
            ->setSubtotal((new Subtotal())->setAmount(300)->setCurrency(self::USD))
            ->setCustomerUser($customerUser)
            ->setValid(true);

        $shoppingList1->addTotal($default);
        $shoppingList1->addTotal($total1);
        $shoppingList2->addTotal($total2);

        $this->totalManager->setSubtotals([$shoppingList1, $shoppingList2], false, $customerUser);

        $this->assertSame(200.0, $shoppingList1->getSubtotal()->getAmount());
        $this->assertSame(300.0, $shoppingList2->getSubtotal()->getAmount());
    }

    public function testRecalculateTotals()
    {
        # Default customer user.
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $shoppingList = new ShoppingList();
        $totalUSD = (new ShoppingListTotal($shoppingList, self::USD))->setCustomerUser($customerUser);
        $totalEUR = (new ShoppingListTotal($shoppingList, self::EUR))->setCustomerUser($customerUser);
        $shoppingList->addTotal($totalUSD)->addTotal($totalEUR);
        $shoppingList->setCustomerUser($customerUser);

        $this->currencyManager
            ->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn([self::EUR, self::USD, self::CAD]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $em
            ->expects($this->once())
            ->method('persist');
        $em
            ->expects($this->once())
            ->method('flush');

        $this->registry
            ->expects($this->once())
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

    public function testRecalculateTotalsWithCurrentCustomer()
    {
        $currentCustomerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 2]);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);

        $this->currencyManager
            ->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn([self::USD]);

        $this->customerUserProvider
            ->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($currentCustomerUser);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $em
            ->expects($this->once())
            ->method('persist');
        $em
            ->expects($this->once())
            ->method('flush');

        $this->registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->subtotalProvider->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn((new Subtotal())->setCurrency(self::USD)->setAmount(100));

        $this->totalManager->recalculateTotals($shoppingList, true);
        $totals = $shoppingList->getTotals();
        $this->assertCount(1, $totals);
        $this->assertEquals($currentCustomerUser, $totals->first()->getCustomerUser());
    }

    public function testGetShoppingListTotalForCurrency(): void
    {
        # Default customer user.
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);
        $totalUSD = (new ShoppingListTotal($shoppingList, self::USD))->setValid(true)->setCustomerUser($customerUser);
        $totalEUR = (new ShoppingListTotal($shoppingList, self::EUR))->setCustomerUser($customerUser);
        $shoppingList->addTotal($totalUSD);
        $shoppingList->addTotal($totalEUR);

        $this->subtotalProvider
            ->expects($this->never())
            ->method('getSubtotalByCurrency');

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this->registry
            ->expects($this->once())
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
        # Default customer user.
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);
        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $expectedTotalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD->setCustomerUser($customerUser);
        $expectedTotalUSD->setSubtotal($subtotal);
        $expectedTotalUSD->setValid(true);

        $this->subtotalProvider
            ->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $em
            ->expects($this->once())
            ->method('persist');
        $this->registry
            ->expects($this->once())
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
        # Default customer user.
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);
        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $expectedTotalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD->setCustomerUser($customerUser);
        $expectedTotalUSD->setSubtotal($subtotal);
        $expectedTotalUSD->setValid(true);

        $this->subtotalProvider
            ->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('contains')
            ->willReturn(false);
        $em
            ->expects($this->never())
            ->method('persist');
        $em
            ->expects($this->never())
            ->method('flush');

        $this->registry
            ->expects($this->once())
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
        # Default customer user.
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);

        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $totalUSD = new ShoppingListTotal($shoppingList, self::USD);

        $expectedTotalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD->setCustomerUser($customerUser);
        $expectedTotalUSD->setSubtotal($subtotal);
        $expectedTotalUSD->setValid(true);
        $shoppingList->addTotal($totalUSD);

        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $totalUSD->setSubtotal($subtotal);

        $this->subtotalProvider
            ->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $this
            ->registry->expects($this->once())
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
        # Default customer user.
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);

        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $totalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $totalUSD->setCustomerUser($customerUser);

        $expectedTotalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $expectedTotalUSD->setCustomerUser($customerUser);
        $expectedTotalUSD->setSubtotal($subtotal);
        $expectedTotalUSD->setValid(true);
        $shoppingList->addTotal($totalUSD);

        $subtotal = (new Subtotal())->setCurrency(self::USD)->setAmount(100);
        $totalUSD->setSubtotal($subtotal);

        $this->subtotalProvider
            ->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('contains')
            ->willReturn(true);
        $em
            ->expects($this->once())
            ->method('flush');

        $this->registry
            ->expects($this->once())
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
