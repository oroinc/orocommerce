<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\CreditCardMethodProvider;
use Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Symfony\Component\Routing\RouterInterface;

class CreditCardMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    const IDENTIFIER1 = 'test1';
    const IDENTIFIER2 = 'test2';
    const WRONG_IDENTIFIER = 'wrong';
    
    /**
     * @var Gateway|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $gateway;

    /**
     * @var PayPalCreditCardConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var PayPalCreditCardConfigInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $paymentConfigs;

    /**
     * @var CreditCardMethodProvider
     */
    protected $creditCardMethodProvider;

    protected function setUp()
    {
        $this->gateway = $this->getMockBuilder(Gateway::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->createMock(RouterInterface::class);
        $this->paymentConfigs = [
            $this->buildPaymentConfig(self::IDENTIFIER1),
            $this->buildPaymentConfig(self::IDENTIFIER2),
        ];
        $this->configProvider = $this->createMock(PayPalCreditCardConfigProviderInterface::class);
        $this->configProvider->expects(static::any())
            ->method('getPaymentConfigs')
            ->willReturn($this->paymentConfigs);
        $this->creditCardMethodProvider = new CreditCardMethodProvider(
            $this->gateway,
            $this->configProvider,
            $this->router
        );
    }

    public function testGetType()
    {
        $type = 'type';
        $this->configProvider->expects(static::once())
            ->method('getType')
            ->willReturn($type);
        static::assertEquals($type, $this->creditCardMethodProvider->getType());
    }

    /**
     * @param string $identifier
     * @param boolean $expectedResult
     * @dataProvider hasPaymentMethodDataProvider
     */
    public function testHasPaymentMethod($identifier, $expectedResult)
    {
        static::assertEquals($expectedResult, $this->creditCardMethodProvider->hasPaymentMethod($identifier));
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
            $this->creditCardMethodProvider->getPaymentMethod(self::IDENTIFIER1)
        );
    }

    public function testGetPaymentMethodForWrongIdentifier()
    {
        static::assertNull($this->creditCardMethodProvider->getPaymentMethod(self::WRONG_IDENTIFIER));
    }

    public function testGetPaymentMethods()
    {
        $expectedMethods = [
            self::IDENTIFIER1 => $this->buildCreditCardMethod($this->paymentConfigs[0]),
            self::IDENTIFIER2 => $this->buildCreditCardMethod($this->paymentConfigs[1]),
        ];

        static::assertEquals(
            $expectedMethods,
            $this->creditCardMethodProvider->getPaymentMethods()
        );
    }

    /**
     * @param string $identifier
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PayPalCreditCardConfigInterface
     */
    private function buildPaymentConfig($identifier)
    {
        $config = $this->createMock(PayPalCreditCardConfigInterface::class);
        $config->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }

    /**
     * @param PayPalCreditCardConfigInterface $config
     *
     * @return PayPalCreditCardPaymentMethod
     */
    private function buildCreditCardMethod(PayPalCreditCardConfigInterface $config)
    {
        return new PayPalCreditCardPaymentMethod(
            $this->gateway,
            $config,
            $this->router
        );
    }
}
