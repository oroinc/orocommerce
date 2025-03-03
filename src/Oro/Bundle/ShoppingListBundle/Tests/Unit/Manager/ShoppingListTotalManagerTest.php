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
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoppingListTotalManagerTest extends TestCase
{
    private const USD = 'USD';
    private const EUR = 'EUR';
    private const CAD = 'CAD';

    private ManagerRegistry&MockObject $doctrine;
    private UserCurrencyManager&MockObject $currencyManager;
    private LineItemNotPricedSubtotalProvider&MockObject $subtotalProvider;
    private CustomerUserProvider&MockObject $customerUserProvider;
    private ShoppingListTotalManager $totalManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->subtotalProvider = $this->createMock(LineItemNotPricedSubtotalProvider::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->customerUserProvider = $this->createMock(CustomerUserProvider::class);

        $this->currencyManager->expects(self::any())
            ->method('getUserCurrency')
            ->willReturn(self::USD);

        $this->totalManager = new ShoppingListTotalManager(
            $this->doctrine,
            $this->subtotalProvider,
            $this->currencyManager,
            $this->customerUserProvider
        );
    }

    private function getCustomerUser(int $id): CustomerUser
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, $id);

        return $customerUser;
    }

    private function getShoppingList(int $id, CustomerUser $customerUser): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);
        $shoppingList->setCustomerUser($customerUser);

        return $shoppingList;
    }

    private function getShoppingListTotal(
        ShoppingList $shoppingList,
        bool $valid,
        string $currency,
        ?Subtotal $subtotal = null,
        ?CustomerUser $customerUser = null,
    ): ShoppingListTotal {
        $shoppingListTotal = new ShoppingListTotal($shoppingList, $currency);
        $shoppingListTotal->setValid($valid);
        if (null !== $subtotal) {
            $shoppingListTotal->setSubtotal($subtotal);
        }
        if (null !== $customerUser) {
            $shoppingListTotal->setCustomerUser($customerUser);
        }

        return $shoppingListTotal;
    }

    private function getSubtotal(float $amount, string $currency): Subtotal
    {
        $subtotal = new Subtotal();
        $subtotal->setAmount($amount);
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    public function testGetNotValidTotal(): void
    {
        # Default customer user.
        $customerUser = $this->getCustomerUser(1);

        $shoppingList1 = $this->getShoppingList(1, $customerUser);
        $shoppingList2 = $this->getShoppingList(2, $customerUser);
        $shoppingList3 = $this->getShoppingList(3, $customerUser);

        $this->customerUserProvider->expects(self::atLeastOnce())
            ->method('getLoggedUser')
            ->willReturn($customerUser);

        // Only $shoppingList3 has total record in DB
        $shoppingList3->addTotal(
            $this->getShoppingListTotal($shoppingList3, true, self::USD, $this->getSubtotal(300, self::USD))
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))
            ->method('persist');
        $em->expects(self::once())
            ->method('flush');

        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->subtotalProvider->expects(self::exactly(2))
            ->method('getSubtotalByCurrency')
            ->withConsecutive(
                [$shoppingList1, self::USD],
                [$shoppingList2, self::USD]
            )
            ->willReturnOnConsecutiveCalls(
                $this->getSubtotal(100, self::USD),
                $this->getSubtotal(200, self::USD)
            );

        $this->totalManager->setSubtotals([$shoppingList1, $shoppingList2, $shoppingList3], false);

        self::assertEquals(self::USD, $shoppingList1->getSubtotal()->getCurrency());
        self::assertEquals(100, $shoppingList1->getSubtotal()->getAmount());
        self::assertEquals(self::USD, $shoppingList2->getSubtotal()->getCurrency());
        self::assertEquals(200, $shoppingList2->getSubtotal()->getAmount());
        self::assertEquals(self::USD, $shoppingList3->getSubtotal()->getCurrency());
        self::assertEquals(300, $shoppingList3->getSubtotal()->getAmount());

        // Ensures that duplicated subtotals will not be created.
        $this->totalManager->setSubtotals([$shoppingList1, $shoppingList2, $shoppingList3]);
    }

    public function testGetWithDifferenceUsers(): void
    {
        $defaultCustomerUser = $this->getCustomerUser(1);
        $customerUser = $this->getCustomerUser(2);

        $shoppingList1 = $this->getShoppingList(1, $defaultCustomerUser);
        $shoppingList2 = $this->getShoppingList(2, $defaultCustomerUser);

        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->willReturn($customerUser);

        $default = $this->getShoppingListTotal(
            $shoppingList1,
            true,
            self::USD,
            $this->getSubtotal(100, self::USD),
            $defaultCustomerUser
        );
        $total1 = $this->getShoppingListTotal(
            $shoppingList1,
            true,
            self::USD,
            $this->getSubtotal(200, self::USD),
            $customerUser
        );
        $total2 = $this->getShoppingListTotal(
            $shoppingList1,
            true,
            self::USD,
            $this->getSubtotal(300, self::USD),
            $customerUser
        );

        $shoppingList1->addTotal($default);
        $shoppingList1->addTotal($total1);
        $shoppingList2->addTotal($total2);

        $this->totalManager->setSubtotals([$shoppingList1, $shoppingList2], false);

        self::assertSame(200.0, $shoppingList1->getSubtotal()->getAmount());
        self::assertSame(300.0, $shoppingList2->getSubtotal()->getAmount());
    }

    public function testRecalculateTotals(): void
    {
        # Default customer user.
        $customerUser = $this->getCustomerUser(1);

        $shoppingList = new ShoppingList();
        $totalUSD = $this->getShoppingListTotal($shoppingList, false, self::USD, null, $customerUser);
        $totalEUR = $this->getShoppingListTotal($shoppingList, false, self::EUR, null, $customerUser);
        $shoppingList->addTotal($totalUSD);
        $shoppingList->addTotal($totalEUR);
        $shoppingList->setCustomerUser($customerUser);

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn([self::EUR, self::USD, self::CAD]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('contains')
            ->willReturn(true);
        $em->expects(self::once())
            ->method('persist');
        $em->expects(self::once())
            ->method('flush');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->subtotalProvider->expects(self::exactly(3))
            ->method('getSubtotalByCurrency')
            ->willReturnMap([
                [$shoppingList, self::USD, $this->getSubtotal(100, self::USD)],
                [$shoppingList, self::EUR, $this->getSubtotal(80, self::EUR)],
                [$shoppingList, self::CAD, $this->getSubtotal(120, self::CAD)],
            ]);

        $this->totalManager->recalculateTotals($shoppingList, true);
        self::assertSame(100.0, $totalUSD->getSubtotal()->getAmount());
        self::assertSame(80.0, $totalEUR->getSubtotal()->getAmount());
    }

    public function testRecalculateTotalsWithCurrentCustomer(): void
    {
        $currentCustomerUser = $this->getCustomerUser(1);
        $customerUser = $this->getCustomerUser(2);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn([self::USD]);

        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->willReturn($currentCustomerUser);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('contains')
            ->willReturn(true);
        $em->expects(self::once())
            ->method('persist');
        $em->expects(self::once())
            ->method('flush');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->subtotalProvider->expects(self::once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($this->getSubtotal(100, self::USD));

        $this->totalManager->recalculateTotals($shoppingList, true);
        $totals = $shoppingList->getTotals();
        self::assertCount(1, $totals);
        self::assertEquals($currentCustomerUser, $totals->first()->getCustomerUser());
    }

    public function testGetShoppingListTotalForCurrency(): void
    {
        # Default customer user.
        $customerUser = $this->getCustomerUser(1);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);
        $totalUSD = $this->getShoppingListTotal($shoppingList, true, self::USD, null, $customerUser);
        $totalEUR = $this->getShoppingListTotal($shoppingList, false, self::EUR, null, $customerUser);
        $shoppingList->addTotal($totalUSD);
        $shoppingList->addTotal($totalEUR);

        $this->subtotalProvider->expects(self::never())
            ->method('getSubtotalByCurrency');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('contains')
            ->willReturn(true);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        self::assertEquals(
            $totalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD)
        );
    }

    public function testGetShoppingListTotalForCurrencyWhenNoEntity(): void
    {
        # Default customer user.
        $customerUser = $this->getCustomerUser(1);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);
        $subtotal = $this->getSubtotal(100, self::USD);
        $expectedTotalUSD = $this->getShoppingListTotal($shoppingList, true, self::USD, $subtotal, $customerUser);

        $this->subtotalProvider->expects(self::once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('contains')
            ->willReturn(true);
        $em->expects(self::once())
            ->method('persist');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        self::assertEquals(
            $expectedTotalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD)
        );
        self::assertCount(1, $shoppingList->getTotals());
        self::assertEquals($expectedTotalUSD, $shoppingList->getTotals()->first());
    }

    public function testGetShoppingListTotalForCurrencyWhenNoEntityAndNotManagedAndDoFlush(): void
    {
        # Default customer user.
        $customerUser = $this->getCustomerUser(1);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);
        $subtotal = $this->getSubtotal(100, self::USD);
        $expectedTotalUSD = $this->getShoppingListTotal($shoppingList, true, self::USD, $subtotal, $customerUser);

        $this->subtotalProvider->expects(self::once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('contains')
            ->willReturn(false);
        $em->expects(self::never())
            ->method('persist');
        $em->expects(self::never())
            ->method('flush');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        self::assertEquals(
            $expectedTotalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD, true)
        );
        self::assertCount(1, $shoppingList->getTotals());
        self::assertEquals($expectedTotalUSD, $shoppingList->getTotals()->first());
    }

    public function testGetShoppingListTotalForCurrencyWhenNotValid(): void
    {
        # Default customer user.
        $customerUser = $this->getCustomerUser(1);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);

        $subtotal = $this->getSubtotal(100, self::USD);
        $totalUSD = $this->getShoppingListTotal($shoppingList, false, self::USD);

        $expectedTotalUSD = $this->getShoppingListTotal($shoppingList, true, self::USD, $subtotal, $customerUser);
        $shoppingList->addTotal($totalUSD);

        $subtotal = $this->getSubtotal(100, self::USD);
        $totalUSD->setSubtotal($subtotal);

        $this->subtotalProvider->expects(self::once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('contains')
            ->willReturn(true);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        self::assertEquals(
            $expectedTotalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD)
        );
        self::assertCount(1, $shoppingList->getTotals());
        self::assertEquals($expectedTotalUSD, $shoppingList->getTotals()->first());
    }

    public function testGetShoppingListTotalForCurrencyWhenNotValidAndDoFlush(): void
    {
        # Default customer user.
        $customerUser = $this->getCustomerUser(1);

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);

        $subtotal = $this->getSubtotal(100, self::USD);
        $totalUSD = $this->getShoppingListTotal($shoppingList, false, self::USD, null, $customerUser);

        $expectedTotalUSD = $this->getShoppingListTotal($shoppingList, true, self::USD, $subtotal, $customerUser);
        $shoppingList->addTotal($totalUSD);

        $subtotal = $this->getSubtotal(100, self::USD);
        $totalUSD->setSubtotal($subtotal);

        $this->subtotalProvider->expects(self::once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn($subtotal);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('contains')
            ->willReturn(true);
        $em->expects(self::once())
            ->method('flush');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        self::assertEquals(
            $expectedTotalUSD,
            $this->totalManager->getShoppingListTotalForCurrency($shoppingList, self::USD, true)
        );
        self::assertCount(1, $shoppingList->getTotals());
        self::assertEquals($expectedTotalUSD, $shoppingList->getTotals()->first());
    }
}
