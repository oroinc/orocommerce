<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\ContextHandler;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderLineItemHandler;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use Oro\Bundle\TaxBundle\Provider\TaxCodeProvider;

class OrderLineItemHandlerTest extends \PHPUnit\Framework\TestCase
{
    const ORDER_LINE_ITEM_CLASS = 'Oro\Bundle\OrderBundle\Entity\OrderLineItem';
    const PRODUCT_TAX_CODE = 'PTC';
    const ACCOUNT_TAX_CODE = 'ATC';
    const ACCOUNT_GROUP_TAX_CODE = 'AGTC';
    const ORDER_ADDRESS_COUNTRY_CODE = 'US';

    /**
     * @var TaxationAddressProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $addressProvider;

    /**
     * @var TaxCodeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxCodeProvider;

    /**
     * @var OrderLineItemHandler
     */
    protected $handler;

    /**
     * @var ProductTaxCode
     */
    protected $productTaxCode;

    /**
     * @var CustomerTaxCode
     */
    protected $customerTaxCode;

    /**
     * @var CustomerTaxCode
     */
    protected $customerGroupTaxCode;

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

    protected function setUp(): void
    {
        $this->productTaxCode = (new ProductTaxCode())
            ->setCode(self::PRODUCT_TAX_CODE);

        $this->order = new Order();

        $this->customerTaxCode = (new CustomerTaxCode())
            ->setCode(self::ACCOUNT_TAX_CODE);

        $this->customerGroupTaxCode = (new CustomerTaxCode())
            ->setCode(self::ACCOUNT_GROUP_TAX_CODE);

        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();

        $this->order->setBillingAddress($billingAddress);
        $this->order->setShippingAddress($shippingAddress);

        $this->addressProvider = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider
            ->expects($this->any())
            ->method('getTaxationAddress')
            ->with($billingAddress, $shippingAddress)
            ->willReturnCallback(
                function () {
                    return $this->address;
                }
            );

        $this->addressProvider
            ->expects($this->any())
            ->method('isDigitalProductTaxCode')
            ->with(self::ORDER_ADDRESS_COUNTRY_CODE, self::PRODUCT_TAX_CODE)
            ->willReturnCallback(
                function () {
                    return $this->isProductTaxCodeDigital;
                }
            );

        $this->taxCodeProvider = $this
            ->getMockBuilder(TaxCodeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new OrderLineItemHandler(
            $this->addressProvider,
            $this->taxCodeProvider,
            self::ORDER_LINE_ITEM_CLASS
        );
    }

    /**
     * @dataProvider onContextEventProvider
     * @param bool $hasProduct
     * @param bool $hasCustomer
     * @param bool $hasProductTaxCode
     * @param bool $hasCustomerTaxCode
     * @param bool $isProductDigital
     * @param OrderAddress|null $taxationAddress
     * @param \ArrayObject $expectedContext
     * @param bool $hasCustomerGroup
     * @param bool $hasCustomerGroupTaxCode
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testOnContextEvent(
        $hasProduct,
        $hasCustomer,
        $hasProductTaxCode,
        $hasCustomerTaxCode,
        $isProductDigital,
        $taxationAddress,
        $expectedContext,
        $hasCustomerGroup = false,
        $hasCustomerGroupTaxCode = false
    ) {
        $this->isProductTaxCodeDigital = $isProductDigital;
        $this->address = $taxationAddress;

        $orderLineItem = new OrderLineItem();

        if ($hasCustomer) {
            $this->order->setCustomer(new Customer());
        }

        if ($hasCustomer && $hasCustomerGroup) {
            $this->order->getCustomer()->setGroup(new CustomerGroup());
        }

        $orderLineItem->setOrder($this->order);

        if ($hasProduct) {
            $orderLineItem->setProduct(new Product());
        }

        if (!$hasProductTaxCode) {
            $this->productTaxCode = null;
        }

        if (!$hasCustomerTaxCode) {
            $this->customerTaxCode = null;
        }

        if (!$hasCustomerGroupTaxCode) {
            $this->customerGroupTaxCode = null;
        }

        $this->taxCodeProvider
            ->expects($this->atLeastOnce())
            ->method('getTaxCode')
            ->willReturnCallback(function ($type) {
                switch ($type) {
                    case TaxCodeInterface::TYPE_PRODUCT:
                        return $this->productTaxCode;
                    case TaxCodeInterface::TYPE_ACCOUNT:
                        return $this->customerTaxCode;
                    case TaxCodeInterface::TYPE_ACCOUNT_GROUP:
                        return $this->customerGroupTaxCode;
                }

                return false;
            });

        $contextEvent = new ContextEvent($orderLineItem);
        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($orderLineItem, $contextEvent->getMappingObject());
        $this->assertEquals($expectedContext, $contextEvent->getContext());

        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($orderLineItem, $contextEvent->getMappingObject());
        $this->assertEquals($expectedContext, $contextEvent->getContext());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function onContextEventProvider()
    {
        $taxationAddress = (new OrderAddress())
            ->setCountry(new Country(self::ORDER_ADDRESS_COUNTRY_CODE));

        return [
            'order line item without product' => [
                'hasProduct' => false,
                'hasCustomer' => true,
                'hasProductTaxCode' => false,
                'hasCustomerTaxCode' => false,
                'isProductDigital' => false,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => false,
                        Taxable::PRODUCT_TAX_CODE => null,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'product is not digital' => [
                'hasProduct' => true,
                'hasCustomer' => true,
                'hasProductTaxCode' => true,
                'hasCustomerTaxCode' => false,
                'isProductDigital' => false,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => false,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'product is digital' => [
                'hasProduct' => true,
                'hasCustomer' => true,
                'hasProductTaxCode' => true,
                'hasCustomerTaxCode' => false,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'product without product tax code' => [
                'hasProduct' => true,
                'hasCustomer' => true,
                'hasProductTaxCode' => false,
                'hasCustomerTaxCode' => false,
                'isProductDigital' => false,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => false,
                        Taxable::PRODUCT_TAX_CODE => null,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'nullable taxation address' => [
                'hasProduct' => true,
                'hasCustomer' => true,
                'hasProductTaxCode' => true,
                'hasCustomerTaxCode' => true,
                'isProductDigital' => true,
                'taxationAddress' => null,
                'expectedContext' => new \ArrayObject([
                    Taxable::DIGITAL_PRODUCT => false,
                    Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                    Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
                ])
            ],
            'order with customer and customer product tax code' => [
                'hasProduct' => true,
                'hasCustomer' => true,
                'hasProductTaxCode' => true,
                'hasCustomerTaxCode' => true,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
                    ]
                ),
            ],
            'order with customer and without customer tax code' => [
                'hasProduct' => true,
                'hasCustomer' => true,
                'hasProductTaxCode' => true,
                'hasCustomerTaxCode' => false,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'order without customer' => [
                'hasProduct' => true,
                'hasCustomer' => false,
                'hasProductTaxCode' => true,
                'hasCustomerTaxCode' => false,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => null,
                    ]
                ),
            ],
            'order with customer Group tax code' => [
                'hasProduct' => true,
                'hasCustomer' => true,
                'hasProductTaxCode' => true,
                'hasCustomerTaxCode' => false,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_GROUP_TAX_CODE,
                    ]
                ),
                'hasCustomerGroup' => true,
                'hasCustomerGroupTaxCode' => true
            ],
            'order without customer Group tax code and customer tax code' => [
                'hasProduct' => true,
                'hasCustomer' => true,
                'hasProductTaxCode' => true,
                'hasCustomerTaxCode' => true,
                'isProductDigital' => true,
                'taxationAddress' => $taxationAddress,
                'expectedContext' => new \ArrayObject(
                    [
                        Taxable::DIGITAL_PRODUCT => true,
                        Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
                        Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
                    ]
                ),
                'hasCustomerGroup' => true,
                'hasCustomerGroupTaxCode' => true
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
