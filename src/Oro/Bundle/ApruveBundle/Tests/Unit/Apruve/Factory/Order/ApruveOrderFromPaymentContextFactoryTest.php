<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Order;

use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilderInterface;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\LineItem\ApruveLineItemFromPaymentLineItemFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\Order\ApruveOrderFromPaymentContextFactory;
use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizerInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProviderInterface;
use Oro\Bundle\ApruveBundle\Provider\TaxAmountProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

class ApruveOrderFromPaymentContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    const MERCHANT_ID = 'sampleMerchantId';
    const AMOUNT = '100.1';
    const TOTAL_AMOUNT_CENTS = 12250;
    const AMOUNT_CENTS = 11130;
    const SHIPPING_AMOUNT = 10.1;
    const SHIPPING_AMOUNT_CENTS = 1010;
    const TAX_AMOUNT = 1.1;
    const TAX_AMOUNT_CENTS = 110;
    const CURRENCY = 'USD';
    const MERCHANT_ORDER_ID = '123';
    const FINALIZE_ON_CREATE = true;
    const INVOICE_ON_CREATE = false;
    const LINE_ITEMS = [
        'sku1' => [
            'sku' => 'sku1',
            'quantity' => 100,
            'currency' => 'USD',
            'amount_cents' => 2000,
        ],
        'sku2' => [
            'sku' => 'sku2',
            'quantity' => 50,
            'currency' => 'USD',
            'amount_cents' => 1000,
        ],
    ];

    /**
     * @var ApruveOrderBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveOrderBuilder;

    /**
     * @var ApruveLineItemFromPaymentLineItemFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveLineItemFromPaymentLineItemFactory;

    /**
     * @var ApruveOrderBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveOrderBuilderFactory;

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContext;

    /**
     * @var ShippingAmountProviderInterface
     */
    private $shippingAmountProvider;

    /**
     * @var TaxAmountProviderInterface
     */
    private $taxAmountProvider;

    /**
     * @var ApruveOrderFromPaymentContextFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $price = $this->createMock(Price::class);
        $price
            ->expects(static::once())
            ->method('getValue')
            ->willReturn(self::AMOUNT);

        $this->paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->paymentContext
            ->expects(static::once())
            ->method('getSubTotal')
            ->willReturn($price);
        $this->paymentContext
            ->expects(static::once())
            ->method('getCurrency')
            ->willReturn(self::CURRENCY);
        $this->paymentContext
            ->expects(static::once())
            ->method('getSourceEntityIdentifier')
            ->willReturn(self::MERCHANT_ORDER_ID);

        $lineItemOne = $this->createMock(PaymentLineItemInterface::class);
        $lineItemTwo = $this->createMock(PaymentLineItemInterface::class);

        $this->paymentContext
            ->expects(static::once())
            ->method('getLineItems')
            ->willReturn([$lineItemOne, $lineItemTwo]);

        $this->shippingAmountProvider = $this->createMock(ShippingAmountProviderInterface::class);
        $this->shippingAmountProvider
            ->expects(static::exactly(2))
            ->method('getShippingAmount')
            ->with($this->paymentContext)
            ->willReturn(self::SHIPPING_AMOUNT);

        $this->taxAmountProvider = $this->createMock(TaxAmountProviderInterface::class);
        $this->taxAmountProvider
            ->expects(static::exactly(2))
            ->method('getTaxAmount')
            ->with($this->paymentContext)
            ->willReturn(self::TAX_AMOUNT);

        $this->apruveOrderBuilder = $this->createMock(ApruveOrderBuilderInterface::class);
        $this->apruveOrderBuilderFactory = $this->createMock(ApruveOrderBuilderFactoryInterface::class);

        $this->apruveLineItemFromPaymentLineItemFactory = $this
            ->createMock(ApruveLineItemFromPaymentLineItemFactoryInterface::class);
        $this->apruveLineItemFromPaymentLineItemFactory
            ->expects(static::exactly(2))
            ->method('createFromPaymentLineItem')
            ->willReturnMap([
                [$lineItemOne, $this->mockApruveLineItem(self::LINE_ITEMS['sku1'])],
                [$lineItemTwo, $this->mockApruveLineItem(self::LINE_ITEMS['sku2'])],
            ]);

        $this->factory = new ApruveOrderFromPaymentContextFactory(
            $this->mockAmountNormalizer(),
            $this->apruveLineItemFromPaymentLineItemFactory,
            $this->shippingAmountProvider,
            $this->taxAmountProvider,
            $this->apruveOrderBuilderFactory
        );
    }

    public function testGetResult()
    {
        $this->apruveOrderBuilderFactory
            ->expects(static::once())
            ->method('create')
            ->with(
                self::MERCHANT_ID,
                self::TOTAL_AMOUNT_CENTS,
                self::CURRENCY,
                [self::LINE_ITEMS['sku1'], self::LINE_ITEMS['sku2']]
            )
            ->willReturn($this->apruveOrderBuilder);

        $this->apruveOrderBuilder
            ->expects(static::once())
            ->method('setMerchantOrderId')
            ->with(self::MERCHANT_ORDER_ID)
            ->willReturnSelf();

        $this->apruveOrderBuilder
            ->expects(static::once())
            ->method('setShippingCents')
            ->with(self::SHIPPING_AMOUNT_CENTS)
            ->willReturnSelf();

        $this->apruveOrderBuilder
            ->expects(static::once())
            ->method('setTaxCents')
            ->with(self::TAX_AMOUNT_CENTS)
            ->willReturnSelf();

        $this->apruveOrderBuilder
            ->expects(static::once())
            ->method('setFinalizeOnCreate')
            ->with(self::FINALIZE_ON_CREATE)
            ->willReturnSelf();

        $this->apruveOrderBuilder
            ->expects(static::once())
            ->method('setInvoiceOnCreate')
            ->with(self::INVOICE_ON_CREATE)
            ->willReturnSelf();

        $this->apruveOrderBuilder
            ->expects(static::once())
            ->method('getResult');

        $this->factory->createFromPaymentContext($this->paymentContext, $this->mockApruveConfig());
    }

    /**
     * @param array $apruveLineItemData
     *
     * @return ApruveLineItemBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockApruveLineItem(array $apruveLineItemData)
    {
        $apruveLineItem = $this->createMock(ApruveLineItem::class);
        $apruveLineItem
            ->expects(static::once())
            ->method('getData')
            ->willReturn($apruveLineItemData);

        return $apruveLineItem;
    }


    /**
     * @return AmountNormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockAmountNormalizer()
    {
        $amountNormalizer = $this->createMock(AmountNormalizerInterface::class);
        $amountNormalizer
            ->method('normalize')
            ->willReturnMap([
                [self::AMOUNT, self::AMOUNT_CENTS],
                [self::SHIPPING_AMOUNT, self::SHIPPING_AMOUNT_CENTS],
                [self::TAX_AMOUNT, self::TAX_AMOUNT_CENTS],
            ]);
        return $amountNormalizer;
    }

    /**
     * @return ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockApruveConfig()
    {
        $config = $this->createMock(ApruveConfigInterface::class);
        $config
            ->expects(static::once())
            ->method('getMerchantId')
            ->willReturn(self::MERCHANT_ID);

        return $config;
    }
}
