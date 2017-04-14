<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Builder\ApruveLineItemBuilderInterface;
use Oro\Bundle\ApruveBundle\Apruve\Builder\ApruveOrderBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Factory\ApruveLineItemBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Request\Order\ApruveOrderRequestDataInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProviderInterface;
use Oro\Bundle\ApruveBundle\Provider\TaxAmountProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

class ApruveOrderBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mandatory
     */
    const MERCHANT_ID = 'sampleMerchantId';
    const AMOUNT = '100.1';
    const AMOUNT_CENTS = 11130;
    const SHIPPING_AMOUNT = 10.1;
    const SHIPPING_AMOUNT_CENTS = 1010;
    const TAX_AMOUNT = 1.1;
    const TAX_AMOUNT_CENTS = 110;
    const CURRENCY = 'USD';
    const MERCHANT_ORDER_ID = '123';

    /**
     * Optional
     */
    const FINALIZE_ON_CREATE = true;
    const INVOICE_ON_CREATE = true;
    const SHOPPER_ID = 'sampleShopperId';
    const CORPORATE_ACCOUNT_ID = 'sampleAccountId';
    const PO_NUMBER = '69000';
    const AUTO_ESCALATE = true;
    const EXPIRE_AT_STRING = '2027-04-15T10:12:27-05:00';

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContext;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var ApruveLineItemBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveLineItemBuilderFactory;

    /**
     * @var ShippingAmountProviderInterface
     */
    private $shippingAmountProvider;

    /**
     * @var TaxAmountProviderInterface
     */
    private $taxAmountProvider;

    /**
     * @var ApruveOrderBuilder
     */
    private $builder;

    /**
     * @var array
     */
    private $apruveLineItems = [
        'sku1' => [
            'sku' => 'sku1',
            'quantity' => 100,
            'currency' => 'USD',
            'price_total_cents' => 2000,
        ],
        'sku2' => [
            'sku' => 'sku2',
            'quantity' => 50,
            'currency' => 'USD',
            'price_total_cents' => 1000,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $price = $this->createMock(Price::class);
        $price
            ->method('getValue')
            ->willReturn(self::AMOUNT);

        $this->paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->paymentContext
            ->method('getSubTotal')
            ->willReturn($price);
        $this->paymentContext
            ->method('getCurrency')
            ->willReturn(self::CURRENCY);
        $this->paymentContext
            ->method('getSourceEntityIdentifier')
            ->willReturn(self::MERCHANT_ORDER_ID);

        $sourceEntity = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['getId'])
            ->getMock();

        $this->paymentContext
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);

        $this->config = $this->createMock(ApruveConfigInterface::class);
        $this->config
            ->method('getMerchantId')
            ->willReturn(self::MERCHANT_ID);

        $this->apruveLineItemBuilderFactory = $this->createMock(ApruveLineItemBuilderFactoryInterface::class);

        $this->shippingAmountProvider = $this->createMock(ShippingAmountProviderInterface::class);
        $this->shippingAmountProvider
            ->method('getShippingAmount')
            ->with($this->paymentContext)
            ->willReturn(self::SHIPPING_AMOUNT);

        $this->taxAmountProvider = $this->createMock(TaxAmountProviderInterface::class);
        $this->taxAmountProvider
            ->method('getTaxAmount')
            ->with($this->paymentContext)
            ->willReturn(self::TAX_AMOUNT);

        $this->builder = new ApruveOrderBuilder(
            $this->paymentContext,
            $this->config,
            $this->apruveLineItemBuilderFactory,
            $this->shippingAmountProvider,
            $this->taxAmountProvider
        );
    }

    public function testGetResult()
    {
        $lineItemOne = $this->createMock(PaymentLineItemInterface::class);
        $lineItemTwo = $this->createMock(PaymentLineItemInterface::class);

        $this->apruveLineItemBuilderFactory
            ->method('create')
            ->willReturnMap([
                [$lineItemOne, $this->createApruveLineItemBuilder($this->apruveLineItems['sku1'])],
                [$lineItemTwo, $this->createApruveLineItemBuilder($this->apruveLineItems['sku2'])],
            ]);

        $this->paymentContext
            ->method('getLineItems')
            ->willReturn([$lineItemOne, $lineItemTwo]);

        $actual = $this->builder->getResult();

        $expected = [
            ApruveOrderBuilder::MERCHANT_ID => self::MERCHANT_ID,
            ApruveOrderBuilder::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveOrderBuilder::CURRENCY => self::CURRENCY,
            ApruveOrderBuilder::MERCHANT_ORDER_ID => self::MERCHANT_ORDER_ID,
            ApruveOrderBuilder::SHIPPING_CENTS => self::SHIPPING_AMOUNT_CENTS,
            ApruveOrderBuilder::TAX_CENTS => self::TAX_AMOUNT_CENTS,
            ApruveOrderBuilder::LINE_ITEMS => array_values($this->apruveLineItems),
        ];
        static::assertEquals($expected, $actual->getData());
    }

    public function testGetResultWithoutLineItems()
    {
        $this->apruveLineItemBuilderFactory
            ->expects(static::never())
            ->method('create');

        $this->paymentContext
            ->method('getLineItems')
            ->willReturn([]);

        $actual = $this->builder->getResult();

        $expected = [
            ApruveOrderBuilder::MERCHANT_ID => self::MERCHANT_ID,
            ApruveOrderBuilder::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveOrderBuilder::CURRENCY => self::CURRENCY,
            ApruveOrderBuilder::MERCHANT_ORDER_ID => self::MERCHANT_ORDER_ID,
            ApruveOrderBuilder::SHIPPING_CENTS => self::SHIPPING_AMOUNT_CENTS,
            ApruveOrderBuilder::TAX_CENTS => self::TAX_AMOUNT_CENTS,
            ApruveOrderBuilder::LINE_ITEMS => [],
        ];
        static::assertEquals($expected, $actual->getData());
    }

    public function testGetResultWithOptionalParams()
    {
        $lineItemOne = $this->createMock(PaymentLineItemInterface::class);
        $lineItemTwo = $this->createMock(PaymentLineItemInterface::class);

        $this->apruveLineItemBuilderFactory
            ->method('create')
            ->willReturnMap([
                [$lineItemOne, $this->createApruveLineItemBuilder($this->apruveLineItems['sku1'])],
                [$lineItemTwo, $this->createApruveLineItemBuilder($this->apruveLineItems['sku2'])],
            ]);

        $this->paymentContext
            ->method('getLineItems')
            ->willReturn([$lineItemOne, $lineItemTwo]);

        $this->builder->setFinalizeOnCreate(self::FINALIZE_ON_CREATE);
        $this->builder->setInvoiceOnCreate(self::INVOICE_ON_CREATE);
        $this->builder->setShopperId(self::SHOPPER_ID);
        $this->builder->setCorporateAccountId(self::CORPORATE_ACCOUNT_ID);
        $this->builder->setPoNumber(self::PO_NUMBER);
        $this->builder->setAutoEscalate(self::AUTO_ESCALATE);
        $this->builder->setExpireAt(\DateTime::createFromFormat(\DateTime::ATOM, self::EXPIRE_AT_STRING));

        $actual = $this->builder->getResult();

        $expected = [
            ApruveOrderBuilder::MERCHANT_ID => self::MERCHANT_ID,
            ApruveOrderBuilder::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveOrderBuilder::CURRENCY => self::CURRENCY,
            ApruveOrderBuilder::LINE_ITEMS => array_values($this->apruveLineItems),
            ApruveOrderBuilder::MERCHANT_ORDER_ID => self::MERCHANT_ORDER_ID,
            ApruveOrderBuilder::SHOPPER_ID => self::SHOPPER_ID,
            ApruveOrderBuilder::SHIPPING_CENTS => self::SHIPPING_AMOUNT_CENTS,
            ApruveOrderBuilder::TAX_CENTS => self::TAX_AMOUNT_CENTS,
            ApruveOrderBuilder::FINALIZE_ON_CREATE => self::FINALIZE_ON_CREATE,
            ApruveOrderBuilder::INVOICE_ON_CREATE => self::INVOICE_ON_CREATE,
            ApruveOrderBuilder::PO_NUMBER => self::PO_NUMBER,
            ApruveOrderBuilder::AUTO_ESCALATE => self::AUTO_ESCALATE,
            ApruveOrderBuilder::EXPIRE_AT => self::EXPIRE_AT_STRING,
            ApruveOrderBuilder::PAYMENT_TERM_PARAMS => [
                ApruveOrderBuilder::_CORPORATE_ACCOUNT_ID => self::CORPORATE_ACCOUNT_ID,
            ],
        ];
        static::assertEquals($expected, $actual->getData());
    }

    /**
     * @param array $apruveLineItem
     * @return ApruveLineItemBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createApruveLineItemBuilder($apruveLineItem)
    {
        $apruveRequestDataOne = $this->createMock(ApruveOrderRequestDataInterface::class);
        $apruveRequestDataOne
            ->method('getData')
            ->willReturn($apruveLineItem);

        $apruveLineItemBuilder = $this->createMock(ApruveLineItemBuilderInterface::class);
        $apruveLineItemBuilder
            ->method('getResult')
            ->willReturn($apruveRequestDataOne);

        return $apruveLineItemBuilder;
    }
}
