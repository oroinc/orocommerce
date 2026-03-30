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
use Oro\Bundle\TaxBundle\Tests\Unit\Stub\OrderLineItemStub;

class OrderLineItemHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_TAX_CODE = 'PTC';
    private const ACCOUNT_TAX_CODE = 'ATC';
    private const ACCOUNT_GROUP_TAX_CODE = 'AGTC';
    private const ORDER_ADDRESS_COUNTRY_CODE = 'US';

    /** @var TaxCodeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxCodeProvider;

    /** @var OrderLineItemHandler */
    private $handler;

    /** @var ProductTaxCode */
    private $productTaxCode;

    /** @var CustomerTaxCode */
    private $customerTaxCode;

    /** @var CustomerTaxCode */
    private $customerGroupTaxCode;

    /** @var Order */
    private $order;

    /** @var OrderAddress */
    private $address;

    /** @var bool */
    private $isProductTaxCodeDigital = false;

    #[\Override]
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

        $addressProvider = $this->createMock(TaxationAddressProvider::class);
        $addressProvider->expects($this->any())
            ->method('getTaxationAddress')
            ->with($billingAddress, $shippingAddress)
            ->willReturnCallback(function () {
                return $this->address;
            });
        $addressProvider->expects($this->any())
            ->method('isDigitalProductTaxCode')
            ->with(self::ORDER_ADDRESS_COUNTRY_CODE, self::PRODUCT_TAX_CODE)
            ->willReturnCallback(function () {
                return $this->isProductTaxCodeDigital;
            });

        $this->taxCodeProvider = $this->createMock(TaxCodeProvider::class);

        $this->handler = new OrderLineItemHandler(
            $addressProvider,
            $this->taxCodeProvider
        );
    }

    /**
     * @dataProvider onContextEventProvider
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testOnContextEvent(
        bool $hasProduct,
        bool $hasCustomer,
        bool $hasProductTaxCode,
        bool $hasCustomerTaxCode,
        bool $isProductDigital,
        ?OrderAddress $taxationAddress,
        \ArrayObject $expectedContext,
        bool $hasCustomerGroup = false,
        bool $hasCustomerGroupTaxCode = false
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

        $orderLineItem->addOrder($this->order);

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

        $this->taxCodeProvider->expects($this->atLeastOnce())
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
     */
    public function onContextEventProvider(): array
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

    public function testOnContextEventWithFreeFormLineItemWithFreeFormTaxCode(): void
    {
        $this->isProductTaxCodeDigital = false;

        $taxationAddress = (new OrderAddress())
            ->setCountry(new Country(self::ORDER_ADDRESS_COUNTRY_CODE));
        $this->address = $taxationAddress;

        $orderLineItem = new OrderLineItemStub();
        $orderLineItem->setFreeFormProduct('Free Form Product');
        $orderLineItem->setFreeFormTaxCode($this->productTaxCode);

        $this->order->setCustomer(new Customer());
        $orderLineItem->addOrder($this->order);

        $this->customerTaxCode = (new CustomerTaxCode())->setCode(self::ACCOUNT_TAX_CODE);

        $this->taxCodeProvider->expects($this->once())
            ->method('getTaxCode')
            ->willReturnCallback(function ($type) {
                if ($type === TaxCodeInterface::TYPE_ACCOUNT) {
                    return $this->customerTaxCode;
                }

                return null;
            });

        $contextEvent = new ContextEvent($orderLineItem);
        $this->handler->onContextEvent($contextEvent);

        $expectedContext = new \ArrayObject([
            Taxable::DIGITAL_PRODUCT => false,
            Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
            Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
        ]);

        $this->assertSame($orderLineItem, $contextEvent->getMappingObject());
        $this->assertEquals($expectedContext, $contextEvent->getContext());
    }

    public function testOnContextEventWithFreeFormLineItemWithoutFreeFormTaxCode(): void
    {
        $this->isProductTaxCodeDigital = false;

        $taxationAddress = (new OrderAddress())
            ->setCountry(new Country(self::ORDER_ADDRESS_COUNTRY_CODE));
        $this->address = $taxationAddress;

        $orderLineItem = new OrderLineItemStub();
        $orderLineItem->setFreeFormProduct('Free Form Product');
        // Not setting freeFormTaxCode

        $this->order->setCustomer(new Customer());
        $orderLineItem->addOrder($this->order);

        $this->customerTaxCode = (new CustomerTaxCode())->setCode(self::ACCOUNT_TAX_CODE);

        $this->taxCodeProvider->expects($this->once())
            ->method('getTaxCode')
            ->willReturnCallback(function ($type) {
                if ($type === TaxCodeInterface::TYPE_ACCOUNT) {
                    return $this->customerTaxCode;
                }
                return null;
            });

        $contextEvent = new ContextEvent($orderLineItem);
        $this->handler->onContextEvent($contextEvent);

        $expectedContext = new \ArrayObject([
            Taxable::DIGITAL_PRODUCT => false,
            Taxable::PRODUCT_TAX_CODE => null,
            Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
        ]);

        $this->assertSame($orderLineItem, $contextEvent->getMappingObject());
        $this->assertEquals($expectedContext, $contextEvent->getContext());
    }

    public function testChecksumIsUsedInLocalCache(): void
    {
        $taxationAddress = (new OrderAddress())
            ->setCountry(new Country(self::ORDER_ADDRESS_COUNTRY_CODE));
        $this->address = $taxationAddress;

        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProduct(new Product());
        $orderLineItem->setChecksum('checksum_v1');

        $this->order->setCustomer(new Customer());
        $orderLineItem->addOrder($this->order);

        $this->productTaxCode = (new ProductTaxCode())->setCode(self::PRODUCT_TAX_CODE);
        $this->customerTaxCode = (new CustomerTaxCode())->setCode(self::ACCOUNT_TAX_CODE);

        $this->taxCodeProvider->expects($this->exactly(4))
            ->method('getTaxCode')
            ->willReturnCallback(function ($type) {
                if ($type === TaxCodeInterface::TYPE_PRODUCT) {
                    return $this->productTaxCode;
                }
                if ($type === TaxCodeInterface::TYPE_ACCOUNT) {
                    return $this->customerTaxCode;
                }

                return null;
            });

        // First call - should retrieve tax codes from provider and cache them
        $contextEvent = new ContextEvent($orderLineItem);
        $this->handler->onContextEvent($contextEvent);

        $expectedContext = new \ArrayObject([
            Taxable::DIGITAL_PRODUCT => false,
            Taxable::PRODUCT_TAX_CODE => self::PRODUCT_TAX_CODE,
            Taxable::ACCOUNT_TAX_CODE => self::ACCOUNT_TAX_CODE,
        ]);

        $this->assertEquals($expectedContext, $contextEvent->getContext());

        // Second call with same checksum - should use cached values (no additional provider calls)
        $contextEvent2 = new ContextEvent($orderLineItem);
        $this->handler->onContextEvent($contextEvent2);

        $this->assertEquals($expectedContext, $contextEvent2->getContext());

        // Change checksum - cache should be invalidated
        $orderLineItem->setChecksum('checksum_v2');

        // Third call with different checksum - should retrieve tax codes again from provider
        $contextEvent3 = new ContextEvent($orderLineItem);
        $this->handler->onContextEvent($contextEvent3);

        $this->assertEquals($expectedContext, $contextEvent3->getContext());
    }
}
