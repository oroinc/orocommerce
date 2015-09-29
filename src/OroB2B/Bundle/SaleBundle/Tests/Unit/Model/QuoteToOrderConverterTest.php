<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Model;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Model\QuoteToOrderConverter;

class QuoteToOrderConverterTest extends \PHPUnit_Framework_TestCase
{
    const CURRENCY = 'USD';

    const ACCOUNT_NAME = 'Test Account';
    const ACCOUNT_USER_FIRST_NAME = 'TestFirstName';
    const ACCOUNT_USER_LAST_NAME = 'TestLastName';

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    /** @var QuoteToOrderConverter */
    protected $converter;

    protected function setUp()
    {
        $this->orderCurrencyHandler = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCurrencyHandler->expects($this->any())
            ->method('setOrderCurrency')
            ->willReturnCallback(
                function (Order $order) {
                    $order->setCurrency(self::CURRENCY);
                }
            );

        $this->converter = new QuoteToOrderConverter($this->orderCurrencyHandler);
    }

    protected function tearDown()
    {
        unset($this->orderCurrencyHandler, $this->converter);
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

        $quoteProduct1 = $this->createQuoteProduct($sku1);
        $quoteProduct1->addQuoteProductOffer(
            $this->createQuoteProductOffer($unit1, $qty1, QuoteProductOffer::PRICE_TYPE_BUNDLED, $pr1, self::CURRENCY)
        );

        $quoteProduct2 = $this->createQuoteProduct($sku2, true);
        $quoteProduct2->setProduct((new Product())->setSku('sku3'));
        $quoteProduct2->addQuoteProductOffer(
            $this->createQuoteProductOffer($unit2, $qty2, QuoteProductOffer::PRICE_TYPE_UNIT, $pr2, self::CURRENCY)
        );

        $quote = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME)
            ->addQuoteProduct($quoteProduct1)
            ->addQuoteProduct($quoteProduct2);

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
            );

        $this->assertEquals($order, $this->converter->convert($quote));
    }

    public function testConvertFromQuoteWithUser()
    {
        $sku = 'sku1';
        $unit = 'kg';
        $qty = 10;
        $pr = 10.5;

        $accountName = 'acc';
        $accountUser = $this->createAccountUser($accountName);

        $quoteProduct = $this->createQuoteProduct($sku);
        $quoteProduct->addQuoteProductOffer(
            $this->createQuoteProductOffer($unit, $qty, QuoteProductOffer::PRICE_TYPE_BUNDLED, $pr, self::CURRENCY)
        );

        $quote = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME)
            ->addQuoteProduct($quoteProduct);

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
            );

        $this->assertEquals($order, $this->converter->convert($quote, $accountUser));
    }

    public function testConvertFromSelectedOffers()
    {
        $sku = 'sku1';
        $unit = 'kg';
        $qty = 55.5;
        $price = 555;

        $quoteProduct = $this->createQuoteProduct($sku, true);
        $quoteProduct->setProduct((new Product())->setSku('test sku'));

        $quote = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME);

        $order = $this
            ->createMainEntity(self::ACCOUNT_NAME, self::ACCOUNT_USER_FIRST_NAME, self::ACCOUNT_USER_LAST_NAME, true)
            ->setCurrency(self::CURRENCY)
            ->addLineItem(
                $this->createOrderLineItem($sku, $unit, $qty, OrderLineItem::PRICE_TYPE_UNIT, $price, self::CURRENCY)
            );

        $offer = $this->createQuoteProductOffer(
            $unit,
            1000,
            QuoteProductOffer::PRICE_TYPE_UNIT,
            $price,
            self::CURRENCY
        );

        $this->createQuoteProduct($sku, true)->addQuoteProductOffer($offer);

        $this->assertEquals($order, $this->converter->convert($quote, null, [['offer' => $offer, 'quantity' => $qty]]));
    }

    /**
     * @param string $accountName
     * @param string $userFirstName
     * @param string $userLastName
     * @param bool $isOrder
     * @return Order|Quote
     */
    protected function createMainEntity($accountName, $userFirstName, $userLastName, $isOrder = false)
    {
        $accountUser = $this->createAccountUser($accountName);

        $owner = new User();
        $owner->setFirstName($userFirstName . ' owner')->setLastName($userLastName . ' owner')->setSalt(null);

        $organization = new Organization();
        $organization->setName($userFirstName . ' ' . $userLastName . ' org');

        $entity = $isOrder ? new Order : new Quote();
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
     * @return AccountUser
     */
    protected function createAccountUser($accountName)
    {
        $accountUser = new AccountUser();
        $accountUser->setFirstName($accountName . ' first')->setLastName($accountName . ' last')->setSalt(null);

        $account = new Account();
        $account->setName($accountName)->addUser($accountUser);

        return $accountUser;
    }
}
