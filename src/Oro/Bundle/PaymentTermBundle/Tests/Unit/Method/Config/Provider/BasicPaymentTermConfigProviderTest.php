<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config\Provider;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Entity\Repository\PaymentTermSettingsRepository;
use Oro\Bundle\PaymentTermBundle\Method\Config\Factory\Settings\PaymentTermConfigBySettingsFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Basic\BasicPaymentTermConfigProvider;

class BasicPaymentTermConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BasicPaymentTermConfigProvider
     */
    private $testedProvider;

    /**
     * @var PaymentTermConfigBySettingsFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTermConfigBySettingsFactoryMock;

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
        $onePaymentMethodId = '1somePaymentMethodId';
        $twoPaymentMethodId = '2somePaymentMethodId';

        $settingsOneMock = $this->createPaymentTermSettingsMock();
        $settingsTwoMock = $this->createPaymentTermSettingsMock();

        $configOneMock = $this->createConfigMock();
        $configTwoMock = $this->createConfigMock();

        $settingsMocks = [$settingsOneMock, $settingsTwoMock];

        $configOneMock
            ->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($onePaymentMethodId);

        $configTwoMock
            ->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($twoPaymentMethodId);

        $this->paymentTermSettingsRepositoryMock
            ->expects($this->once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $this->paymentTermConfigBySettingsFactoryMock
            ->method('createConfigBySettings')
            ->will(
                $this->returnValueMap(
                    [
                        [$settingsOneMock, $configOneMock],
                        [$settingsTwoMock, $configTwoMock]
                    ]
                )
            );

        $expectedResult = [
            $onePaymentMethodId => $configOneMock,
            $twoPaymentMethodId => $configTwoMock
        ];

        $actualResult = $this->testedProvider->getPaymentConfigs();

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testGetPaymentConfig()
    {
        $onePaymentMethodId = '1somePaymentMethodId';
        $twoPaymentMethodId = '2somePaymentMethodId';

        $settingsOneMock = $this->createPaymentTermSettingsMock();
        $settingsTwoMock = $this->createPaymentTermSettingsMock();

        $configOneMock = $this->createConfigMock();
        $configTwoMock = $this->createConfigMock();

        $settingsMocks = [$settingsOneMock, $settingsTwoMock];

        $configOneMock
            ->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($onePaymentMethodId);

        $configTwoMock
            ->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($twoPaymentMethodId);

        $this->paymentTermSettingsRepositoryMock
            ->expects($this->once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $this->paymentTermConfigBySettingsFactoryMock
            ->method('createConfigBySettings')
            ->will(
                $this->returnValueMap(
                    [
                        [$settingsOneMock, $configOneMock],
                        [$settingsTwoMock, $configTwoMock]
                    ]
                )
            );

        $expectedResult = $configOneMock;
        $actualResult = $this->testedProvider->getPaymentConfig($onePaymentMethodId);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testGetPaymentConfigWhenNoSettings()
    {
        $this->paymentTermSettingsRepositoryMock
            ->expects($this->once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        $actualResult = $this->testedProvider->getPaymentConfig('somePaymentMethodId');

        $this->assertEquals(null, $actualResult);
    }

    public function testHasPaymentConfig()
    {
        $onePaymentMethodId = '1somePaymentMethodId';
        $twoPaymentMethodId = '2somePaymentMethodId';

        $settingsOneMock = $this->createPaymentTermSettingsMock();
        $settingsTwoMock = $this->createPaymentTermSettingsMock();

        $configOneMock = $this->createConfigMock();
        $configTwoMock = $this->createConfigMock();

        $settingsMocks = [$settingsOneMock, $settingsTwoMock];

        $configOneMock
            ->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($onePaymentMethodId);

        $configTwoMock
            ->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($twoPaymentMethodId);

        $this->paymentTermSettingsRepositoryMock
            ->expects($this->once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $this->paymentTermConfigBySettingsFactoryMock
            ->method('createConfigBySettings')
            ->will(
                $this->returnValueMap(
                    [
                        [$settingsOneMock, $configOneMock],
                        [$settingsTwoMock, $configTwoMock]
                    ]
                )
            );

        $expectedResult = true;
        $actualResult = $this->testedProvider->hasPaymentConfig($onePaymentMethodId);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testHasPaymentConfigWhenNoSettings()
    {
        $this->paymentTermSettingsRepositoryMock
            ->expects($this->once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        $actualResult = $this->testedProvider->hasPaymentConfig('somePaymentMethodId');

        $this->assertEquals(null, $actualResult);
    }
}
