<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductsPricesDataProvider;

class FrontendShoppingListProductsPricesDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const TEST_CURRENCY = 'USD';

    /**
     * @var FrontendShoppingListProductsPricesDataProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserCurrencyProvider
     */
    protected $userCurrencyProvider;

    public function setUp()
    {
        $this->productPriceProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userCurrencyProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendShoppingListProductsPricesDataProvider(
            $this->productPriceProvider,
            $this->securityFacade,
            $this->userCurrencyProvider
        );
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined data item index: shoppingList.
     */
    public function testGetDataWithEmptyContext()
    {
        $context = new LayoutContext();
        $this->provider->getData($context);
    }

    /**
     * @dataProvider getDataDataProvider
     * @param ShoppingList|null $shoppingList
     * @param ProductPriceCriteria $criteria
     * @param Price $price
     * @param AccountUser|null $accountUser
     */
    public function testGetData($shoppingList, ProductPriceCriteria $criteria, Price $price, $accountUser)
    {
        $context = new LayoutContext();
        $context->data()->set('shoppingList', null, $shoppingList);
        $expected = null;

        if ($shoppingList) {
            $this->securityFacade->expects($this->once())
                ->method('getLoggedUser')
                ->willReturn($accountUser);

            if ($accountUser) {
                $this->userCurrencyProvider->expects($this->once())
                    ->method('getUserCurrency')
                    ->willReturn(self::TEST_CURRENCY);

                $this->productPriceProvider->expects($this->once())
                    ->method('getMatchedPrices')
                    ->with([$criteria])
                    ->willReturn([
                        $criteria->getIdentifier() => $price
                    ]);

                $expected = ['42' => $price];
            }
        }

        $result = $this->provider->getData($context);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 42]);
        $productUnit = new ProductUnit();
        $productUnit->setCode('test');
        $quantity = 100;

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setUnit($productUnit);
        $lineItem->setQuantity($quantity);

        $criteria = new ProductPriceCriteria($product, $productUnit, $quantity, self::TEST_CURRENCY);

        $price = new Price();
        $price->setValue('123');
        $price->setCurrency(self::TEST_CURRENCY);

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);
        
        return [
            'with account user' => [
                'shoppingList' => $shoppingList,
                'criteria' => $criteria,
                'price' => $price,
                'accountUser' => new AccountUser()
            ],
            'without account user' => [
                'shoppingList' => $shoppingList,
                'criteria' => $criteria,
                'price' => $price,
                'accountUser' => null
            ],
            'without shoppingList' => [
                'shoppingList' => null,
                'criteria' => $criteria,
                'price' => $price,
                'accountUser' => new AccountUser()
            ]
        ];
    }
}
