<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Model\QuoteToOrderConverter;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrderBundle\Handler\OrderTotalsHandler;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QuoteToOrderConverterTest extends \PHPUnit_Framework_TestCase
{
    const CURRENCY = 'USD';

    const ACCOUNT_NAME = 'Test Account';
    const ACCOUNT_USER_FIRST_NAME = 'TestFirstName';
    const ACCOUNT_USER_LAST_NAME = 'TestLastName';

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TotalProcessorProvider */
    protected $totalsProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemSubtotalProvider */
    protected $subTotalLineItemProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderTotalsHandler */
    protected $orderTotalsHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var QuoteToOrderConverter */
    protected $converter;

    protected function setUp()
    {
        $this->orderCurrencyHandler = $this
            ->getMockBuilder('Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCurrencyHandler->expects($this->any())
            ->method('setOrderCurrency')
            ->willReturnCallback(
                function (Order $order) {
                    $order->setCurrency(self::CURRENCY);
                }
            );

        $this->totalsProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subTotalLineItemProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderTotalsHelper = $this
            ->getMockBuilder('Oro\Bundle\OrderBundle\Handler\OrderTotalsHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->converter = new QuoteToOrderConverter(
            $this->orderCurrencyHandler,
            $this->orderTotalsHelper,
            $this->registry
        );
    }

    protected function tearDown()
    {
        unset(
            $this->orderCurrencyHandler,
            $this->subTotalLineItemProvider,
            $this->registry,
            $this->converter,
            $this->totalsProvider,
            $this->orderTotalsHelper
        );
    }

    public function testConvertFromQuote()
    {
        $sku1 = 'sku1';
        $sku2 = 'sku2';

        $unit1 = 'kg';
        $unit2 = 'item';

        $qty1 = 10;
        $qty2 = 55.5;

        $pr1 = 10.5;
        $pr2 = 555;

        $subtotalAmount =  10500.5;
        $totalAmount = 20500.5;

        $subtotalObject = $this->createMultiCurrencyObjectForOrder($subtotalAmount);
        $totalObject = $this->createMultiCurrencyObjectForOrder($totalAmount);

        $quoteProduct1 = $this->createQuoteProduct($sku1);
        $quoteProduct1->addQuoteProductOffer(
            $this->createQuoteProductOffer($unit1, $qty1, QuoteProductOffer::PRICE_TYPE_BUNDLED, $pr1, self::CURRENCY)
        );

        $quoteProduct2 = $this->createQuoteProduct($sku2, true);
        $quoteProduct2->setProduct((new Product())->setSku('sku3'));
        $quoteProduct2->addQuoteProductOffer(
            $this->createQuoteProductOffer($unit2, $qty2, QuoteProductOffer::PRICE_TYPE_UNIT, $pr2, self::CURRENCY)
        );
        $shippingAddress = $this->createShippingAddress();

        $quoteShippingEstimateValue = 222.33;
        /** @var Quote $quote */
        $quote = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME)
            ->addQuoteProduct($quoteProduct1)
            ->addQuoteProduct($quoteProduct2);
        $quote->setCurrency(self::CURRENCY)
              ->setEstimatedShippingCostAmount($quoteShippingEstimateValue);

        $order = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME, true)
            ->setCurrency(self::CURRENCY)
            ->addLineItem(
                $this->createOrderLineItem(
                    $sku1,
                    $unit1,
                    $qty1,
                    OrderLineItem::PRICE_TYPE_BUNDLED,
                    $pr1,
                    self::CURRENCY
                )
            )
            ->addLineItem(
                $this->createOrderLineItem(
                    $sku2,
                    $unit2,
                    $qty2,
                    OrderLineItem::PRICE_TYPE_UNIT,
                    $pr2,
                    self::CURRENCY
                )
            )
            ->setSubtotalObject(clone $subtotalObject)
            ->setTotalObject(clone $totalObject)
            ->setShippingAddress($shippingAddress)
            ->setEstimatedShippingCostAmount($quoteShippingEstimateValue)
            ->setSourceEntityClass('Oro\Bundle\SaleBundle\Entity\Quote')
            ->setSourceEntityId(0);

        $this->orderTotalsHelper
            ->expects($this->once())
            ->method('fillSubtotals')
            ->willReturnCallback(
                function (Order $order) use ($subtotalObject, $totalObject) {
                    $order->setSubtotalObject($subtotalObject);
                    $order->setTotalObject($totalObject);
                }
            );

        $this->assertEquals($order, $this->converter->convert($quote));
    }

    public function testConvertFromQuoteWithUser()
    {
        $sku = 'sku1';
        $unit = 'kg';
        $qty = 10;
        $pr = 10.5;
        $subtotalAmount = 1050.5;
        $totalAmount = 2050.5;

        $subtotalObject = $this->createMultiCurrencyObjectForOrder($subtotalAmount);
        $totalObject = $this->createMultiCurrencyObjectForOrder($totalAmount);

        $accountName = 'acc';
        $accountUser = $this->createAccountUser($accountName);

        $quoteProduct = $this->createQuoteProduct($sku);
        $quoteProduct->addQuoteProductOffer(
            $this->createQuoteProductOffer($unit, $qty, QuoteProductOffer::PRICE_TYPE_BUNDLED, $pr, self::CURRENCY)
        );

        $shippingAddress = $this->createShippingAddress();

        $quoteShippingEstimateValue = 222.33;
        /** @var Quote $quote */
        $quote = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME)
            ->addQuoteProduct($quoteProduct);
        $quote->setCurrency(self::CURRENCY)
            ->setEstimatedShippingCostAmount($quoteShippingEstimateValue);

        $order = $this
            ->createMainEntity($accountName, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME, true)
            ->setCurrency(self::CURRENCY)
            ->setAccountUser($accountUser)
            ->setAccount($accountUser->getAccount())
            ->addLineItem(
                $this->createOrderLineItem(
                    $sku,
                    $unit,
                    $qty,
                    OrderLineItem::PRICE_TYPE_BUNDLED,
                    $pr,
                    self::CURRENCY
                )
            )
            ->setSubtotalObject($subtotalObject)
            ->setTotalObject($totalObject)
            ->setShippingAddress($shippingAddress)
            ->setEstimatedShippingCostAmount($quoteShippingEstimateValue)
            ->setSourceEntityClass('Oro\Bundle\SaleBundle\Entity\Quote')
            ->setSourceEntityId(0);

        $this->orderTotalsHelper
            ->expects($this->once())
            ->method('fillSubtotals')
            ->willReturnCallback(
                function (Order $order) use ($subtotalObject, $totalObject) {
                    $order->setSubtotalObject($subtotalObject);
                    $order->setTotalObject($totalObject);
                }
            );

        $this->assertEquals($order, $this->converter->convert($quote, $accountUser));
    }

    /**
     * @dataProvider convertWithFlushDataProvider
     *
     * @param bool $needFlush
     */
    public function testConvertFromSelectedOffers($needFlush)
    {
        $sku = 'sku1';
        $unit = 'kg';
        $qty = 55.5;
        $price = 555;
        $subtotalAmount = 25355.5;
        $totalAmount = 55355.5;

        $subtotalObject = $this->createMultiCurrencyObjectForOrder($subtotalAmount);
        $totalObject = $this->createMultiCurrencyObjectForOrder($totalAmount);

        $quoteProduct = $this->createQuoteProduct($sku, true);
        $quoteProduct->setProduct((new Product())->setSku('test sku'));
        $shippingAddress = $this->createShippingAddress();

        $quoteShippingEstimateValue = 222.33;
        $quote = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME);
        $quote->setCurrency(self::CURRENCY)
            ->setEstimatedShippingCostAmount($quoteShippingEstimateValue);

        $order = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME, true)
            ->setCurrency(self::CURRENCY)
            ->addLineItem(
                $this->createOrderLineItem($sku, $unit, $qty, OrderLineItem::PRICE_TYPE_UNIT, $price, self::CURRENCY)
            )
            ->setSubtotalObject($subtotalObject)
            ->setTotalObject($totalObject)
            ->setShippingAddress($shippingAddress)
            ->setEstimatedShippingCostAmount($quoteShippingEstimateValue)
            ->setSourceEntityClass('Oro\Bundle\SaleBundle\Entity\Quote')
            ->setSourceEntityId(0);

        $offer = $this->createQuoteProductOffer(
            $unit,
            1000,
            QuoteProductOffer::PRICE_TYPE_UNIT,
            $price,
            self::CURRENCY
        );

        $this->createQuoteProduct($sku, true)->addQuoteProductOffer($offer);

        $this->orderTotalsHelper
            ->expects($this->once())
            ->method('fillSubtotals')
            ->willReturnCallback(
                function (Order $order) use ($subtotalObject, $totalObject) {
                    $order->setSubtotalObject($subtotalObject);
                    $order->setTotalObject($totalObject);
                }
            );

        if ($needFlush) {
            $this->assertDoctrineCalled();
        }

        $this->assertEquals(
            $order,
            $this->converter->convert($quote, null, [['offer' => $offer, 'quantity' => $qty]], $needFlush)
        );
    }

    public function testConvertFromQuoteWithEMptyShippingAddress()
    {
        $sku = 'sku1';
        $unit = 'kg';
        $qty = 55.5;
        $price = 555;
        $subtotalAmount = 25355.5;
        $totalAmount = 55355.5;

        $subtotalObject = $this->createMultiCurrencyObjectForOrder($subtotalAmount);
        $totalObject = $this->createMultiCurrencyObjectForOrder($totalAmount);

        $quoteProduct = $this->createQuoteProduct($sku, true);
        $quoteProduct->setProduct((new Product())->setSku('test sku'));

        $quote = $this->createMainEntity(
            self::ACCOUNT_NAME,
            self::ACCOUNT_USER_FIRST_NAME,
            self::ACCOUNT_USER_LAST_NAME,
            false,
            true
        );
        $quoteShippingEstimateValue = 222.33;
        $quote->setCurrency(self::CURRENCY)
            ->setEstimatedShippingCostAmount($quoteShippingEstimateValue);

        $order = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME, true)
            ->setCurrency(self::CURRENCY)
            ->addLineItem(
                $this->createOrderLineItem($sku, $unit, $qty, OrderLineItem::PRICE_TYPE_UNIT, $price, self::CURRENCY)
            )
            ->setSubtotalObject($subtotalObject)
            ->setTotalObject($totalObject)
            ->setEstimatedShippingCostAmount($quoteShippingEstimateValue)
            ->setSourceEntityClass('Oro\Bundle\SaleBundle\Entity\Quote')
            ->setSourceEntityId(0);

        $offer = $this->createQuoteProductOffer(
            $unit,
            1000,
            QuoteProductOffer::PRICE_TYPE_UNIT,
            $price,
            self::CURRENCY
        );

        $this->createQuoteProduct($sku, true)->addQuoteProductOffer($offer);

        $this->orderTotalsHelper
            ->expects($this->once())
            ->method('fillSubtotals')
            ->willReturnCallback(
                function (Order $order) use ($subtotalObject, $totalObject) {
                    $order->setSubtotalObject($subtotalObject);
                    $order->setTotalObject($totalObject);
                }
            );

        $this->assertEquals(
            $order,
            $this->converter->convert($quote, null, [['offer' => $offer, 'quantity' => $qty]])
        );
    }

    /**
     * @return array
     */
    public function convertWithFlushDataProvider()
    {
        return [
            [false],
            [true]
        ];
    }

    /**
     * @param string $accountName
     * @param string $userFirstName
     * @param string $userLastName
     * @param bool $isOrder
     * @param bool $emptyShippingAddress
     *
     * @return Order|Quote
     */
    protected function createMainEntity(
        $accountName,
        $userFirstName,
        $userLastName,
        $isOrder = false,
        $emptyShippingAddress = false
    ) {
        $accountUser = $this->createAccountUser($accountName);

        $owner = new User();
        $owner->setFirstName($userFirstName . ' owner')->setLastName($userLastName . ' owner')->setSalt(null);

        $organization = new Organization();
        $organization->setName($userFirstName . ' ' . $userLastName . ' org');

        $entity = $isOrder ? new Order : new Quote();

        if ($entity instanceof Quote) {
            if (!$emptyShippingAddress) {
                $shippingAddress = new QuoteAddress();
                $shippingAddress->setAccountAddress(new CustomerAddress());
                $shippingAddress->setAccountUserAddress(new CustomerUserAddress());
                $shippingAddress->setLabel('Label');
                $shippingAddress->setStreet('Street');
                $shippingAddress->setStreet2('Street');
                $shippingAddress->setCity('City');
                $shippingAddress->setPostalCode('Postal code');
                $shippingAddress->setOrganization('Organization');
                $shippingAddress->setRegionText('Region text');
                $shippingAddress->setNamePrefix('Prefix');
                $shippingAddress->setFirstName('First Name');
                $shippingAddress->setMiddleName('Middle Name');
                $shippingAddress->setLastName('Last Name');
                $shippingAddress->setNameSuffix('Suffix');
                $shippingAddress->setRegion(null);
                $shippingAddress->setCountry(null);
                $shippingAddress->setPhone('21312312123');
                $entity->setShippingAddress($shippingAddress);
            }
        }

        $entity
            ->setAccount($accountUser->getAccount())
            ->setAccountUser($accountUser)
            ->setOwner($owner)
            ->setOrganization($organization);

        return $entity;
    }

    /**
     * @param string $sku
     * @param bool $isReplacement
     * @return QuoteProduct
     */
    protected function createQuoteProduct($sku, $isReplacement = false)
    {
        $product = new Product();
        $product->setSku($sku);

        $quoteProduct = new QuoteProduct();
        if ($isReplacement) {
            $quoteProduct->setType(QuoteProduct::TYPE_NOT_AVAILABLE);
            $quoteProduct->setProductReplacement($product);
        } else {
            $quoteProduct->setProduct($product);
        }

        return $quoteProduct;
    }

    /**
     * @param string $unitCode
     * @param float $quantity
     * @param float $priceValue
     * @param string $priceCurrency
     * @param int $priceType
     * @return QuoteProductOffer
     */
    protected function createQuoteProductOffer($unitCode, $quantity, $priceType, $priceValue, $priceCurrency)
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $price = Price::create($priceValue, $priceCurrency);

        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer
            ->setProductUnit($unit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setPriceType($priceType);

        return $quoteProductOffer;
    }

    /**
     * @param string $sku
     * @param string $unitCode
     * @param float $qty
     * @param int $priceType
     * @param float $priceValue
     * @param string $priceCurrency
     * @return OrderLineItem
     */
    protected function createOrderLineItem($sku, $unitCode, $qty, $priceType, $priceValue, $priceCurrency)
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem
            ->setProduct((new Product)->setSku($sku))
            ->setProductUnit((new ProductUnit)->setCode($unitCode))
            ->setQuantity($qty)
            ->setPriceType($priceType)
            ->setPrice(Price::create($priceValue, $priceCurrency))
            ->setFromExternalSource(true);

        return $orderLineItem;
    }

    /**
     * @param string $accountName
     * @return CustomerUser
     */
    protected function createAccountUser($accountName)
    {
        $accountUser = new CustomerUser();
        $accountUser->setFirstName($accountName . ' first')->setLastName($accountName . ' last')->setSalt(null);

        $account = new Customer();
        $account->setName($accountName)->addUser($accountUser);

        return $accountUser;
    }

    protected function assertDoctrineCalled()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('Oro\Bundle\OrderBundle\Entity\Order'));
        $manager->expects($this->once())
            ->method('flush');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroOrderBundle:Order')
            ->willReturn($manager);
    }

    /**
     * @return OrderAddress
     */
    protected function createShippingAddress()
    {
        $shippingAddress = new OrderAddress();

        $shippingAddress->setAccountAddress(new CustomerAddress());
        $shippingAddress->setAccountUserAddress(new CustomerUserAddress());
        $shippingAddress->setLabel('Label');
        $shippingAddress->setStreet('Street');
        $shippingAddress->setStreet2('Street');
        $shippingAddress->setCity('City');
        $shippingAddress->setPostalCode('Postal code');
        $shippingAddress->setOrganization('Organization');
        $shippingAddress->setRegionText('Region text');
        $shippingAddress->setNamePrefix('Prefix');
        $shippingAddress->setFirstName('First Name');
        $shippingAddress->setMiddleName('Middle Name');
        $shippingAddress->setLastName('Last Name');
        $shippingAddress->setNameSuffix('Suffix');
        $shippingAddress->setRegion(null);
        $shippingAddress->setCountry(null);
        $shippingAddress->setPhone('21312312123');

        $shippingAddress->setFromExternalSource(true);

        return $shippingAddress;
    }

    protected function createMultiCurrencyObjectForOrder($value)
    {
        return MultiCurrency::create($value, self::CURRENCY, $value);
    }
}
