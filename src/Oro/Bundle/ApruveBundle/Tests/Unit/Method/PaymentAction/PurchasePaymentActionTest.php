<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderInterface;
use Oro\Bundle\ApruveBundle\Apruve\Generator\OrderSecureHashGeneratorInterface;
use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrderInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\PurchasePaymentAction;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Psr\Log\LoggerInterface;

class PurchasePaymentActionTest extends \PHPUnit_Framework_TestCase
{
    const APRUVE_ORDER = [
        ApruveOrderBuilder::MERCHANT_ID => 'sampleId',
        ApruveOrderBuilder::AMOUNT_CENTS => 10000,
        ApruveOrderBuilder::CURRENCY => 'USD',
        ApruveOrderBuilder::SHIPPING_CENTS => '500',
        ApruveOrderBuilder::FINALIZE_ON_CREATE => true,
        ApruveOrderBuilder::INVOICE_ON_CREATE => false,
        ApruveOrderBuilder::LINE_ITEMS => [
            [
                ApruveLineItemBuilder::TITLE => 'Sample title',
                ApruveLineItemBuilder::PRICE_TOTAL_CENTS => 10000,
                ApruveLineItemBuilder::QUANTITY => 10,
                ApruveLineItemBuilder::DESCRIPTION => "Sample" . PHP_EOL . "description with line break",
                ApruveLineItemBuilder::SKU => 'sku1',
                ApruveLineItemBuilder::VIEW_PRODUCT_URL => 'http://example.com/product/view/1',
            ],
        ],
        ApruveOrderBuilder::FINALIZE_ON_CREATE => true,
        ApruveOrderBuilder::INVOICE_ON_CREATE => false,
    ];
    const SECURE_HASH = '6c6b4a10f9afc452a065051ff42da575264d937f77888b4397fd85d0c12d2109';

    const INITIAL_OPTIONS = ['some_option' => 'option_value'];

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var ApruveOrderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveOrder;

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContext;

    /**
     * @var ApruveOrderBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveOrderBuilderFactory;

    /**
     * @var OrderSecureHashGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderSecureHashGenerator;

    /**
     * @var PurchasePaymentAction
     */
    private $paymentAction;

    /**
     * @var TransactionPaymentContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContextFactory;

    /**
     * @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTransaction;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;


    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->paymentTransaction = $this->createMock(PaymentTransaction::class);
        $this->paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->paymentContextFactory = $this->createMock(TransactionPaymentContextFactoryInterface::class);
        $this->apruveOrder = $this->createMock(ApruveOrderInterface::class);
        $this->apruveOrderBuilderFactory = $this->createMock(ApruveOrderBuilderFactoryInterface::class);
        $this->orderSecureHashGenerator = $this->createMock(OrderSecureHashGeneratorInterface::class);
        $this->config = $this->createMock(ApruveConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->paymentAction = new PurchasePaymentAction(
            $this->paymentContextFactory,
            $this->apruveOrderBuilderFactory,
            $this->orderSecureHashGenerator
        );
        $this->paymentAction->setLogger($this->logger);
    }

    public function testExecute()
    {
        $this->paymentContextFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->paymentTransaction)
            ->willReturn($this->paymentContext);

        $this->apruveOrder
            ->expects(static::once())
            ->method('getData')
            ->willReturn(self::APRUVE_ORDER);

        $this->apruveOrderBuilderFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->paymentContext)
            ->willReturn($this->getApruveOrderBuilder());

        $this->orderSecureHashGenerator
            ->expects(static::once())
            ->method('generate')
            ->with($this->apruveOrder, $this->config)
            ->willReturn(self::SECURE_HASH);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('getTransactionOptions')
            ->willReturn(self::INITIAL_OPTIONS);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setTransactionOptions')
            ->with(self::INITIAL_OPTIONS + ['apruveOrder' => self::APRUVE_ORDER]);

        $this->paymentTransaction
            ->expects(static::once())
            ->method('setSuccessful')
            ->with(false);
        $this->paymentTransaction
            ->expects(static::once())
            ->method('setActive')
            ->with(true);

        $actual = $this->paymentAction->execute($this->config, $this->paymentTransaction);

        $expected = [
            'apruveOrder' => self::APRUVE_ORDER,
            'apruveOrderSecureHash' => self::SECURE_HASH,
        ];
        static::assertSame($expected, $actual);
    }

    public function testExecuteWithoutPaymentContext()
    {
        $this->paymentContextFactory
            ->expects(static::once())
            ->method('create')
            ->with($this->paymentTransaction)
            ->willReturn(null);

        $this->apruveOrderBuilderFactory
            ->expects(static::never())
            ->method('create');

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with(
                static::isType('string'),
                static::logicalAnd(
                    static::isType('array'),
                    static::isEmpty()
                )
            );

        $actual = $this->paymentAction->execute($this->config, $this->paymentTransaction);

        static::assertSame([], $actual);
    }

    public function testGetName()
    {
        $actual = $this->paymentAction->getName();

        static::assertSame(PurchasePaymentAction::NAME, $actual);
    }

    /**
     * @return ApruveOrderBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getApruveOrderBuilder()
    {
        /** @var ApruveOrderBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $apruveOrderBuilder */
        $apruveOrderBuilder = $this->createMock(ApruveOrderBuilderInterface::class);

        $apruveOrderBuilder
            ->expects(static::once())
            ->method('setFinalizeOnCreate')
            ->with(true)
            ->willReturnSelf();
        $apruveOrderBuilder
            ->expects(static::once())
            ->method('setInvoiceOnCreate')
            ->with(false);
        $apruveOrderBuilder
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($this->apruveOrder);

        return $apruveOrderBuilder;
    }
}
