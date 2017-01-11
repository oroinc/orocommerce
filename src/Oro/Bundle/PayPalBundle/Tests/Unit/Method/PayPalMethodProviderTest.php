<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout;
use Oro\Bundle\PayPalBundle\Method\PayflowGateway;
use Oro\Bundle\PayPalBundle\Method\PayPalMethodProvider;
use Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro;
use Oro\Bundle\PayPalBundle\Method\PayPalPaymentsProExpressCheckout;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

class PayPalMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $providerType;

    /**
     * @var Gateway|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $gateway;

    /**
     * @var PaymentConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ExtractOptionsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $optionsProvider;

    /**
     * @var SurchargeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $surchargeProvider;

    protected function setUp()
    {
        $this->prepareMocks();
    }

    /**
     * @param string $type
     * @param PayflowGatewayConfigInterface|PayflowExpressCheckoutConfigInterface $config
     * @param PaymentMethodInterface $expectedMethod
     * @dataProvider paymentMethodsDataProvider
     */
    public function testGetPaymentMethods($type, $config, $expectedMethod)
    {
        $payPalMethodProvider = new PayPalMethodProvider(
            $type,
            $this->gateway,
            $config,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );
        static::assertEquals([$type => $expectedMethod], $payPalMethodProvider->getPaymentMethods());
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
               'expectedMethod' => new PayflowGateway(
                   $this->gateway,
                   $creditCardConfig,
                   $this->router
               ),
           ],
           [
               'type' => 'paypal_payments_pro',
               'config' => $creditCardConfig,
               'expectedMethod' => new PayPalPaymentsPro(
                   $this->gateway,
                   $creditCardConfig,
                   $this->router
               ),
           ],
           [
               'type' => 'payflow_express_checkout',
               'config' => $expressCheckoutConfig,
               'expectedMethod' => new PayflowExpressCheckout(
                   $this->gateway,
                   $expressCheckoutConfig,
                   $this->router,
                   $this->doctrineHelper,
                   $this->optionsProvider,
                   $this->surchargeProvider
               ),
           ],
            [
                'type' => 'paypal_payments_pro_express_checkout',
                'config' => $expressCheckoutConfig,
                'expectedMethod' => new PayPalPaymentsProExpressCheckout(
                    $this->gateway,
                    $expressCheckoutConfig,
                    $this->router,
                    $this->doctrineHelper,
                    $this->optionsProvider,
                    $this->surchargeProvider
                ),
            ]
        ];
    }

    /**
     * @param string $type
     * @param PayflowGatewayConfigInterface|PayflowExpressCheckoutConfigInterface $config
     * @param PaymentMethodInterface $expectedMethod
     * @dataProvider paymentMethodsDataProvider
     */
    public function testGetPaymentMethod($type, $config, $expectedMethod)
    {
        $payPalMethodProvider = new PayPalMethodProvider(
            $type,
            $this->gateway,
            $config,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );
        static::assertEquals($expectedMethod, $payPalMethodProvider->getPaymentMethod($type));
    }

    /**
     * @param string $type
     * @param PayflowGatewayConfigInterface|PayflowExpressCheckoutConfigInterface $config
     * @dataProvider paymentMethodsDataProvider
     */
    public function testHasPaymentMethod($type, $config)
    {
        $payPalMethodProvider = new PayPalMethodProvider(
            $type,
            $this->gateway,
            $config,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );
        static::assertTrue($payPalMethodProvider->hasPaymentMethod($type));
    }

    public function testGetType()
    {
        $payPalMethodProvider = new PayPalMethodProvider(
            'payment_provider',
            $this->gateway,
            $this->config,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );
        static::assertEquals('payment_provider', $payPalMethodProvider->getType());
    }

    protected function prepareMocks()
    {
        $this->gateway = $this->getMockBuilder(Gateway::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->createMock(PaymentConfigInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsProvider = $this->getMockBuilder(ExtractOptionsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->surchargeProvider = $this->getMockBuilder(SurchargeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
