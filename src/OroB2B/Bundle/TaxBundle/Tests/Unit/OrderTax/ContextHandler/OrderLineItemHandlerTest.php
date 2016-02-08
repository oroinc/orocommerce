<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\OrderTax\ContextHandler;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Event\ContextEvent;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\OrderTax\ContextHandler\OrderLineItemHandler;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;

class OrderLineItemHandlerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_TAX_CODE_CLASS = 'PRODUCT_TAX_CODE_CLASS';
    const ORDER_LINE_ITEM_CLASS = 'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem';
    const PRODUCT_TAX_CODE = 'PTC';
    const ORDER_ADDRESS_COUNTRY_CODE = 'US';

    /**
     * @var TaxationAddressProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressProvider;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ProductTaxCodeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productTaxCodeRepository;

    /**
     * @var OrderLineItemHandler
     */
    protected $handler;

    /**
     * @var ProductTaxCode
     */
    protected $productTaxCode;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var OrderAddress
     */
    protected $address;

    /**
     * @var bool
     */
    protected $isProductTaxCodeDigital = false;

    protected function setUp()
    {
        $this->productTaxCode = (new ProductTaxCode())
            ->setCode(self::PRODUCT_TAX_CODE);

        $this->order = new Order();

        $this->address = (new OrderAddress())
            ->setCountry(new Country(self::ORDER_ADDRESS_COUNTRY_CODE));

        $this->addressProvider = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider
            ->expects($this->any())
            ->method('getAddressForTaxation')
            ->with($this->order)
            ->willReturn($this->address);

        $this->addressProvider
            ->expects($this->any())
            ->method('isDigitalProductTaxCode')
            ->with(self::ORDER_ADDRESS_COUNTRY_CODE, self::PRODUCT_TAX_CODE)
            ->willReturnCallback(function () {
                return $this->isProductTaxCodeDigital;
            });

        $this->productTaxCodeRepository = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productTaxCodeRepository
            ->expects($this->any())
            ->method('findOneByProduct')
            ->willReturn($this->productTaxCode);

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(self::PRODUCT_TAX_CODE_CLASS)
            ->willReturn($this->productTaxCodeRepository);

        $this->handler = new OrderLineItemHandler(
            $this->addressProvider,
            $this->doctrineHelper,
            self::PRODUCT_TAX_CODE_CLASS,
            self::ORDER_LINE_ITEM_CLASS
        );
    }

    protected function tearDown()
    {
        unset($this->handler, $this->doctrineHelper, $this->addressProvider);
    }

    /**
     * @dataProvider onContextEventProvider
     * @param bool $hasProduct
     * @param bool $isProductDigital
     * @param \ArrayObject $expectedContext
     */
    public function testOnContextEvent($hasProduct, $isProductDigital, $expectedContext)
    {
        $this->isProductTaxCodeDigital = $isProductDigital;

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setOrder($this->order);

        if ($hasProduct) {
            $orderLineItem->setProduct(new Product());
        }

        $contextEvent = new ContextEvent($orderLineItem);
        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($orderLineItem, $contextEvent->getMappingObject());
        $this->assertEquals($expectedContext, $contextEvent->getContext());
    }

    /**
     * @return array
     */
    public function onContextEventProvider()
    {
        return [
            'order line item without product' => [
                'hasProduct' => false,
                'isProductDigital' => false,
                'expectedContext' => new \ArrayObject([
                    Taxable::DIGITAL_PRODUCT => false,
                    Taxable::PRODUCT_TAX_CODE => null,
                ])
            ],
            'product is not digital' => [
                'hasProduct' => true,
                'isProductDigital' => false,
                'expectedContext' => new \ArrayObject([
                    Taxable::DIGITAL_PRODUCT => false,
                    Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                ])
            ],
            'product is digital' => [
                'hasProduct' => true,
                'isProductDigital' => true,
                'expectedContext' => new \ArrayObject([
                    Taxable::DIGITAL_PRODUCT => true,
                    Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                ])
            ],
        ];
    }

    public function testIncorrectOrderLineItemClass()
    {
        $object = new \stdClass();
        $contextEvent = new ContextEvent($object);
        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($object, $contextEvent->getMappingObject());
        $this->assertEmpty($contextEvent->getContext());
    }
}
