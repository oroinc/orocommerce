<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckoutView;
use Oro\Bundle\PayPalBundle\Method\View\PayflowGatewayView;
use Oro\Bundle\PayPalBundle\Method\View\PayPalMethodViewProvider;
use Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProExpressCheckoutView;
use Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProView;
use Symfony\Component\Form\FormFactoryInterface;

class PayPalMethodViewProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $providerType;

    /**
     * @var PaymentConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    /**
     * @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransactionProvider;

    /**
     * @var PayPalMethodViewProvider
     */
    protected $payPalMethodViewProvider;

    protected function setUp()
    {
        $this->prepareMocks();
    }

    /**
     * @param string $type
     * @param PayflowGatewayConfigInterface|PayflowExpressCheckoutConfigInterface $config
     * @param PaymentMethodViewInterface $expectedMethod
     * @dataProvider paymentMethodsDataProvider
     */
    public function testGetPaymentMethodViews($type, $config, $expectedMethod)
    {
        $payPalMethodProvider = new PayPalMethodViewProvider(
            $type,
            $config,
            $this->formFactory,
            $this->paymentTransactionProvider
        );
        static::assertEquals([$type => $expectedMethod], $payPalMethodProvider->getPaymentMethodViews([$type]));
    }

    /**
     * @param string $type
     * @param PayflowGatewayConfigInterface|PayflowExpressCheckoutConfigInterface $config
     * @param PaymentMethodViewInterface $expectedMethod
     * @dataProvider paymentMethodsDataProvider
     */
    public function testGetPaymentMethodView($type, $config, $expectedMethod)
    {
        $payPalMethodProvider = new PayPalMethodViewProvider(
            $type,
            $config,
            $this->formFactory,
            $this->paymentTransactionProvider
        );
        static::assertEquals($expectedMethod, $payPalMethodProvider->getPaymentMethodView($type));
    }

    /**
     * @param string $type
     * @param PayflowGatewayConfigInterface|PayflowExpressCheckoutConfigInterface $config
     * @dataProvider paymentMethodsDataProvider
     */
    public function testHasPaymentMethodView($type, $config)
    {
        $payPalMethodProvider = new PayPalMethodViewProvider(
            $type,
            $config,
            $this->formFactory,
            $this->paymentTransactionProvider
        );
        static::assertTrue($payPalMethodProvider->hasPaymentMethodView($type));
    }

    /**
     * @return array
     */
    public function paymentMethodsDataProvider()
    {
        $this->prepareMocks();
        $creditCardConfig = $this->createMock(PayflowGatewayConfigInterface::class);
        $expressCheckoutConfig = $this->createMock(PayflowExpressCheckoutConfigInterface::class);

        return [
            [
                'type' => 'payflow_gateway',
                'config' => $creditCardConfig,
                'expectedMethod' => new PayflowGatewayView(
                    $this->formFactory,
                    $creditCardConfig,
                    $this->paymentTransactionProvider
                ),
            ],
            [
                'type' => 'paypal_payments_pro',
                'config' => $creditCardConfig,
                'expectedMethod' => new PayPalPaymentsProView(
                    $this->formFactory,
                    $creditCardConfig,
                    $this->paymentTransactionProvider
                ),
            ],
            [
                'type' => 'payflow_express_checkout',
                'config' => $expressCheckoutConfig,
                'expectedMethod' => new PayflowExpressCheckoutView(
                    $expressCheckoutConfig
                ),
            ],
            [
                'type' => 'paypal_payments_pro_express_checkout',
                'config' => $expressCheckoutConfig,
                'expectedMethod' => new PayPalPaymentsProExpressCheckoutView(
                    $expressCheckoutConfig
                ),
            ]
        ];
    }

    protected function prepareMocks()
    {
        $this->config = $this->createMock(PaymentConfigInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->paymentTransactionProvider = $this->getMockBuilder(PaymentTransactionProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
