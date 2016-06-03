<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Provider\ShoppingListSubtotalProvider;

class ShoppingListSubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var LineItemNotPricedSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lineItemNotPricedSubtotalProvider;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyManager;

    /**
     * @var ShoppingListSubtotalProvider
     */
    protected $shoppingListSubtotalProvider;

    protected function setUp()
    {
        $this->managerRegistry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->lineItemNotPricedSubtotalProvider = $this->getMockBuilder(LineItemNotPricedSubtotalProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->currencyManager = $this->getMockBuilder(UserCurrencyManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shoppingListSubtotalProvider = new ShoppingListSubtotalProvider(
            $this->managerRegistry,
            $this->lineItemNotPricedSubtotalProvider,
            $this->currencyManager
        );
    }

    /**
     * @dataProvider getSubtotalDataProvider
     * @param ShoppingListTotal|null $shoppingListTotal
     */
    public function testGetSubtotal(ShoppingListTotal $shoppingListTotal = null)
    {
        $shoppingList = new ShoppingList();
        $currency = 'USD';
        $subtotal = new Subtotal();
        $amountFromProvider = 3.22;
        if (!($shoppingListTotal && $shoppingListTotal->isValid() === true)) {
            $this->lineItemNotPricedSubtotalProvider
                ->expects($this->once())
                ->method('getSubtotal')
                ->with($shoppingList)
                ->willReturn((new Subtotal())->setAmount($amountFromProvider));
        }
        $this->currencyManager->expects($this->once())->method('getUserCurrency')->willReturn($currency);
        /** @var  \PHPUnit_Framework_MockObject_MockObject|EntityRepository $shoppingListTotalRepo */
        $shoppingListTotalRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListTotalRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['shoppingList' => $shoppingList, 'currency' => $currency])
            ->willReturn($shoppingListTotal);
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $shoppingListTotalEntityManager */
        $shoppingListTotalEntityManager = $this->getMock(EntityManagerInterface::class);
        $shoppingListTotalEntityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BShoppingListBundle:ShoppingListTotal')
            ->willReturn($shoppingListTotalRepo);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BShoppingListBundle:ShoppingListTotal')
            ->willReturn($shoppingListTotalEntityManager);
        $this->lineItemNotPricedSubtotalProvider
            ->expects($this->once())
            ->method('createSubtotal')
            ->willReturn($subtotal);
        if ($shoppingListTotal && $shoppingListTotal->isValid() === false) {
            $shoppingListTotalEntityManager->expects($this->once())->method('flush');
        }
        if (!$shoppingListTotal) {
            $newShoppingListTotal = new ShoppingListTotal();
            $newShoppingListTotal->setValid(true);
            $newShoppingListTotal->setCurrency($currency);
            $newShoppingListTotal->setShoppingList($shoppingList);
            $newShoppingListTotal->setSubtotalValue($amountFromProvider);
            $shoppingListTotalEntityManager->expects($this->once())
                ->method('persist')
                ->with($newShoppingListTotal);
        }
        $this->shoppingListSubtotalProvider->getSubtotal($shoppingList);
    }

    /**
     * @return array
     */
    public function getSubtotalDataProvider()
    {
        $validShoppingListTotal = new ShoppingListTotal();
        $validShoppingListTotal->setValid(true);
        $notValidShoppingListTotal = clone $validShoppingListTotal;
        $notValidShoppingListTotal->setValid(false);

        return ['null' => [null], 'valid' => [$validShoppingListTotal], 'notValid' => [$validShoppingListTotal]];
    }
}
