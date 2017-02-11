<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config\Provider;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Entity\Repository\PaymentTermSettingsRepository;
use Oro\Bundle\PaymentTermBundle\Method\Config\Factory\Settings\PaymentTermConfigBySettingsFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Basic\BasicPaymentTermConfigProvider;

class BasicPaymentTermConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    const IDENTIFIER1 = 'payment_method_1';
    const IDENTIFIER2 = 'payment_method_2';

    /**
     * @var BasicPaymentTermConfigProvider
     */
    private $testedProvider;

    /**
     * @var PaymentTermConfigBySettingsFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTermConfigBySettingsFactoryMock;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var PaymentTermSettingsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTermSettingsRepositoryMock;

    public function setUp()
    {
        $this->paymentTermConfigBySettingsFactoryMock = $this->createMock(
            PaymentTermConfigBySettingsFactoryInterface::class
        );

        $this->paymentTermSettingsRepositoryMock = $this->createMock(PaymentTermSettingsRepository::class);

        $settingsOneMock = $this->createPaymentTermSettingsMock();
        $settingsTwoMock = $this->createPaymentTermSettingsMock();

        $configOneMock = $this->createConfigMock();
        $configTwoMock = $this->createConfigMock();

        $settingsMocks = [$settingsOneMock, $settingsTwoMock];

        $configOneMock
            ->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn(self::IDENTIFIER1);

        $configTwoMock
            ->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn(self::IDENTIFIER2);

        $this->paymentTermSettingsRepositoryMock
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $this->paymentTermConfigBySettingsFactoryMock
            ->method('createConfigBySettings')
            ->will(
                static::returnValueMap(
                    [
                        [$settingsOneMock, $configOneMock],
                        [$settingsTwoMock, $configTwoMock]
                    ]
                )
            );
        $this->configs = [
            self::IDENTIFIER1 => $configOneMock,
            self::IDENTIFIER2 => $configTwoMock
        ];

        $this->testedProvider = new BasicPaymentTermConfigProvider(
            $this->paymentTermConfigBySettingsFactoryMock,
            $this->paymentTermSettingsRepositoryMock
        );
    }

    /**
     * @return PaymentTermSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPaymentTermSettingsMock()
    {
        return $this->createMock(PaymentTermSettings::class);
    }

    /**
     * @return PaymentTermConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createConfigMock()
    {
        return $this->createMock(PaymentTermConfigInterface::class);
    }

    public function testGetPaymentConfigs()
    {
        $actualResult = $this->testedProvider->getPaymentConfigs();

        static::assertEquals($this->configs, $actualResult);
    }

    public function testGetPaymentConfig()
    {
        $expectedResult = $this->configs[self::IDENTIFIER1];
        $actualResult = $this->testedProvider->getPaymentConfig(self::IDENTIFIER1);

        static::assertEquals($expectedResult, $actualResult);
    }

    public function testGetPaymentConfigWhenNoSettings()
    {
        $this->paymentTermSettingsRepositoryMock
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        $actualResult = $this->testedProvider->getPaymentConfig('somePaymentMethodId');

        static::assertEquals(null, $actualResult);
    }

    public function testHasPaymentConfig()
    {
        $expectedResult = true;
        $actualResult = $this->testedProvider->hasPaymentConfig(self::IDENTIFIER1);

        static::assertEquals($expectedResult, $actualResult);
    }

    public function testHasPaymentConfigWhenNoSettings()
    {
        $this->paymentTermSettingsRepositoryMock
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        $actualResult = $this->testedProvider->hasPaymentConfig('somePaymentMethodId');

        static::assertEquals(null, $actualResult);
    }
}
