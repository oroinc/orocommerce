<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

class ShoppingListTotalManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const USD = 'USD';
    const EUR = 'EUR';
    const CAD = 'CAD';
    /**
     * @var ShoppingListTotalManager
     */
    protected $totalManager;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyManager;

    /**
     * @var LineItemNotPricedSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subtotalProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();

        $this->currencyManager = $this->getMockBuilder(UserCurrencyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyManager->method('getUserCurrency')->willReturn(self::USD);

        $this->subtotalProvider = $this->getMockBuilder(LineItemNotPricedSubtotalProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();

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

        $repository = $this->getMock(ObjectRepository::class);
        $repository->expects($this->once())->method('findBy')->willReturn([$total]);

        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->once())->method('getRepository')->willReturn($repository);
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->once())->method('flush');
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($em);

        $this->subtotalProvider->expects($this->at(0))
            ->method('getSubtotalByCurrency')
            ->with($shoppingList1, self::USD)
            ->willReturn((new Subtotal())->setCurrency(self::USD)->setAmount(100));

        $this->subtotalProvider->expects($this->at(1))
            ->method('getSubtotalByCurrency')
            ->with($shoppingList2, self::USD)
            ->willReturn((new Subtotal())->setCurrency(self::USD)->setAmount(200));

        $this->totalManager->setSubtotals([$shoppingList1, $shoppingList2, $shoppingList3]);
        $this->assertEquals(self::USD, $shoppingList1->getSubtotal()->getCurrency());
        $this->assertEquals(100, $shoppingList1->getSubtotal()->getAmount());
        $this->assertEquals(self::USD, $shoppingList2->getSubtotal()->getCurrency());
        $this->assertEquals(200, $shoppingList2->getSubtotal()->getAmount());
        $this->assertEquals(self::USD, $shoppingList3->getSubtotal()->getCurrency());
        $this->assertEquals(300, $shoppingList3->getSubtotal()->getAmount());
    }

    public function testRecalculateTotals()
    {
        $shoppingList = new ShoppingList();
        $totalUSD = new ShoppingListTotal($shoppingList, self::USD);
        $totalEUR = new ShoppingListTotal($shoppingList, self::EUR);
        $this->currencyManager->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn([self::EUR, self::USD, self::CAD]);

        $repository = $this->getMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$totalUSD, $totalEUR]);

        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->once())->method('getRepository')->willReturn($repository);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($em);

        $this->subtotalProvider->expects($this->exactly(3))
            ->method('getSubtotalByCurrency')
            ->willReturnMap([
                [$shoppingList, self::USD, (new Subtotal())->setCurrency(self::USD)->setAmount(100)],
                [$shoppingList, self::EUR, (new Subtotal())->setCurrency(self::EUR)->setAmount(80)],
                [$shoppingList, self::CAD, (new Subtotal())->setCurrency(self::CAD)->setAmount(120)],
            ]);

        $this->totalManager->recalculateTotals($shoppingList, true);
        $this->assertSame(100, $totalUSD->getSubtotal()->getAmount());
        $this->assertSame(80, $totalEUR->getSubtotal()->getAmount());
    }
}
