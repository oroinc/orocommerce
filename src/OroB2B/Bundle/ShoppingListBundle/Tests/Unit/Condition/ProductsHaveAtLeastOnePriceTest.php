<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Condition\ProductsHaveAtLeastOnePrice;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ProductsHaveAtLeastOnePriceTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const PROPERTY_PATH_NAME = 'testPropertyPath';

    /**
     * @param array $lineItems
     * @param null $user
     * @param array $prices
     * @return ProductsHaveAtLeastOnePrice
     */
    private function createCondition($lineItems = [], $user = null, $prices = [])
    {
        /**
         * @var ProductPriceProvider $productPriceProvider
         */
        $productPriceProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getMatchedPrices'])
            ->getMock();

        $productPriceProvider->expects($this->any())
            ->method('getMatchedPrices')
            ->will($this->returnValue($prices));

        /**
         * @var SecurityFacade $securityFacade
         */
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade->method('getLoggedUser')
            ->will($this->returnValue($user));

        /**
         * @var UserCurrencyProvider $userCurrencyProvider
         */
        $userCurrencyProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $userCurrencyProvider->method('getUserCurrency')
            ->will($this->returnValue('USD'));

        /**
         * @var PriceListRequestHandler $priceListRequestHandler
         */
        $priceListRequestHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $priceListRequestHandler->expects($this->any())
            ->method('getPriceListByAccount')
            ->will($this->returnValue(new BasePriceList()));

        $condition = new ProductsHaveAtLeastOnePrice(
            $productPriceProvider,
            $securityFacade,
            $userCurrencyProvider,
            $priceListRequestHandler
        );

        $propertyPath = $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPathInterface')
            ->getMock();

        $propertyPath->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue(self::PROPERTY_PATH_NAME));

        $propertyPath->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue([self::PROPERTY_PATH_NAME]));

        $condition->initialize([$propertyPath]);

        /** @var ContextAccessorInterface $contextAccessor */
        $contextAccessor = $this->getMockBuilder('Oro\Component\ConfigExpression\ContextAccessorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($lineItems));

        $condition->setContextAccessor($contextAccessor);

        return $condition;
    }

    public function testGetName()
    {
        $condition = $this->createCondition();

        $this->assertEquals(ProductsHaveAtLeastOnePrice::NAME, $condition->getName());
    }

    public function testToArray()
    {
        $condition = $this->createCondition();
        $toArray = $condition->toArray();

        $key = '@'.ProductsHaveAtLeastOnePrice::NAME;

        $this->assertInternalType('array', $toArray);
        $this->assertArrayHasKey($key, $toArray);

        $resultSection = $toArray[$key];

        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains('$'.self::PROPERTY_PATH_NAME, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $condition = $this->createCondition();

        $result = $condition->compile('$factory');

        $this->assertContains('$factory->create', $result);
    }

    /**
     * @dataProvider contextProvider
     *
     * @param $context
     * @param LineItem[] $lineItems
     * @param AccountUser $user
     * @param Price[] $prices
     */
    public function testEvaluates($context, $lineItems, $user, $prices, $expectedResult)
    {
        $condition = $this->createCondition($lineItems, $user, $prices);

        $this->assertEquals($condition->evaluate($context), $expectedResult);
    }

    /**
     * @return array
     */
    public function contextProvider()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 1]);

        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 2]);

        /** @var ProductUnit $productUnit */
        $productUnit = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'test']);

        $lineItem->setProduct($product);
        $lineItem->setUnit($productUnit);
        $lineItem->setQuantity(2);

        /** @var LineItem[] $lineItems */
        $lineItems = [
            $lineItem
        ];

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', ['id' => 2]);

        $price = new Price();
        $price->setValue('123');
        $price->setCurrency('USD');

        return [
            [
                $shoppingList,
                $lineItems,
                new AccountUser(),
                [$price],
                true
            ],
            [
                $shoppingList,
                $lineItems,
                new AccountUser(),
                [],
                false
            ],
            [
                $shoppingList,
                $lineItems,
                null,
                [],
                false
            ]
        ];
    }
}
