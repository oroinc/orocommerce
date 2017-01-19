<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\ExpressCheckoutMethodProvider;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

class ExpressCheckoutMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    const IDENTIFIER1 = 'test1';
    const IDENTIFIER2 = 'test2';
    const WRONG_IDENTIFIER = 'wrong';

    /**
     * @var Gateway|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $gateway;

    /**
     * @var PayPalExpressCheckoutConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

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

    /**
     * @var PayPalExpressCheckoutConfigInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $paymentConfigs;

    /**
     * @var ExpressCheckoutMethodProvider
     */
    protected $expressCheckoutMethodProvider;

    protected function setUp()
    {
        $this->gateway = $this->getMockBuilder(Gateway::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentConfigs = [
            $this->buildPaymentConfig(self::IDENTIFIER1),
            $this->buildPaymentConfig(self::IDENTIFIER2),
        ];
        $this->configProvider = $this->createMock(PayPalExpressCheckoutConfigProviderInterface::class);
        $this->configProvider->expects(static::any())
            ->method('getPaymentConfigs')
            ->willReturn($this->paymentConfigs);
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
        $this->expressCheckoutMethodProvider = new ExpressCheckoutMethodProvider(
            $this->gateway,
            $this->configProvider,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );
    }

    public function testGetType()
    {
        $type = 'type';
        $this->configProvider->expects(static::once())
            ->method('getType')
            ->willReturn($type);
        static::assertEquals(
            sprintf('%s_%s', $type, ExpressCheckoutMethodProvider::TYPE),
            $this->expressCheckoutMethodProvider->getType()
        );
    }

    /**
     * @param string $identifier
     * @param boolean $expectedResult
     * @dataProvider hasPaymentMethodDataProvider
     */
    public function testHasPaymentMethod($identifier, $expectedResult)
    {
        static::assertEquals($expectedResult, $this->expressCheckoutMethodProvider->hasPaymentMethod($identifier));
    }

    /**
     * @return array
     */
    public function hasPaymentMethodDataProvider()
    {
        return [
            'existingIdentifier' => [
                'identifier' => self::IDENTIFIER1,
                'expectedResult' => true,
            ],
            'notExistingIdentifier' => [
                'identifier' => self::WRONG_IDENTIFIER,
                'expectedResult' => false,
            ]
        ];
    }

    public function testGetPaymentMethodForCorrectIdentifier()
    {
        $expectedMethod = $this->buildCreditCardMethod($this->paymentConfigs[0]);

        static::assertEquals(
            $expectedMethod,
            $this->expressCheckoutMethodProvider->getPaymentMethod(self::IDENTIFIER1)
        );
    }

    public function testGetPaymentMethodForWrongIdentifier()
    {
        static::assertNull($this->expressCheckoutMethodProvider->getPaymentMethod(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethods()
    {
        $expectedMethods = [
            self::IDENTIFIER1 => $this->buildCreditCardMethod($this->paymentConfigs[0]),
            self::IDENTIFIER2 => $this->buildCreditCardMethod($this->paymentConfigs[1]),
        ];

        static::assertEquals(
            $expectedMethods,
            $this->expressCheckoutMethodProvider->getPaymentMethods()
        );
    }


    /**
     * @param string $identifier
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PayPalExpressCheckoutConfigInterface
     */
    private function buildPaymentConfig($identifier)
    {
        $config = $this->createMock(PayPalExpressCheckoutConfigInterface::class);
        $config->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }

    /**
     * @param PayPalExpressCheckoutConfigInterface $config
     *
     * @return PayPalExpressCheckoutPaymentMethod
     */
    private function buildCreditCardMethod(PayPalExpressCheckoutConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethod(
            $this->gateway,
            $config,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider
        );
    }
}
