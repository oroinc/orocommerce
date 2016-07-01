<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

class ShoppingListTotalManagerTest extends \PHPUnit_Framework_TestCase
{
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

    public function testGetExistingValidTotal()
    {
        $shoppingList = new ShoppingList();
        $total = new ShoppingListTotal($shoppingList, self::USD);
        $total->setSubtotal((new Subtotal())->setCurrency(self::USD)->setAmount(100))
            ->setValid(true);

        $repository = $this->getMock(ObjectRepository::class);
        $repository->expects($this->once())->method('findOneBy')->willReturn($total);
        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->once())->method('getRepository')->willReturn($repository);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($em);

        $this->subtotalProvider->expects($this->never())->method('getSubtotalByCurrency');

        $subtotal = $this->totalManager->getSubtotal($shoppingList);
        $this->assertSame(self::USD, $subtotal->getCurrency());
        $this->assertSame(100, $subtotal->getAmount());
    }

    public function testGetNotValidTotal()
    {
        $shoppingList = new ShoppingList();

        $repository = $this->getMock(ObjectRepository::class);
        $repository->expects($this->once())->method('findOneBy')->willReturn(null);

        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->once())->method('getRepository')->willReturn($repository);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($em);

        $this->subtotalProvider->expects($this->once())
            ->method('getSubtotalByCurrency')
            ->with($shoppingList, self::USD)
            ->willReturn((new Subtotal())->setCurrency(self::USD)->setAmount(100));

        $subtotal = $this->totalManager->getSubtotal($shoppingList);
        $this->assertSame(self::USD, $subtotal->getCurrency());
        $this->assertSame(100, $subtotal->getAmount());
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
