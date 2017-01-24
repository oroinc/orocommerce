<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config\Provider;

use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Entity\Repository\MoneyOrderSettingsRepository;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProvider;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;

class MoneyOrderConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MoneyOrderConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configFactory;

    /**
     * @var MoneyOrderSettingsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $settingsRepository;

    /**
     * @var MoneyOrderConfigProviderInterface
     */
    private $testedProvider;

    public function setUp()
    {
        $this->configFactory = $this->createMock(MoneyOrderConfigFactoryInterface::class);
        $this->settingsRepository = $this->createMock(MoneyOrderSettingsRepository::class);

        $this->testedProvider = new MoneyOrderConfigProvider($this->settingsRepository, $this->configFactory);
    }

    /**
     * @return MoneyOrderSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMoneyOrderSettingsMock()
    {
        return $this->createMock(MoneyOrderSettings::class);
    }

    /**
     * @return MoneyOrderConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createConfigMock()
    {
        return $this->createMock(MoneyOrderConfigInterface::class);
    }

    public function testGetPaymentConfigs()
    {
        $onePaymentMethodId = '1somePaymentMethodId';
        $twoPaymentMethodId = '2somePaymentMethodId';

        $settingsOneMock = $this->createMoneyOrderSettingsMock();
        $settingsTwoMock = $this->createMoneyOrderSettingsMock();

        $configOneMock = $this->createConfigMock();
        $configTwoMock = $this->createConfigMock();

        $settingsMocks = [$settingsOneMock, $settingsTwoMock];

        $configOneMock
            ->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($onePaymentMethodId);

        $configTwoMock
            ->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($twoPaymentMethodId);

        $this->settingsRepository
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $this->configFactory
            ->method('create')
            ->will(
                static::returnValueMap(
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

        static::assertEquals($expectedResult, $actualResult);
    }

    public function testGetPaymentConfig()
    {
        $onePaymentMethodId = '1somePaymentMethodId';
        $twoPaymentMethodId = '2somePaymentMethodId';

        $settingsOneMock = $this->createMoneyOrderSettingsMock();
        $settingsTwoMock = $this->createMoneyOrderSettingsMock();

        $configOneMock = $this->createConfigMock();
        $configTwoMock = $this->createConfigMock();

        $settingsMocks = [$settingsOneMock, $settingsTwoMock];

        $configOneMock
            ->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($onePaymentMethodId);

        $configTwoMock
            ->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($twoPaymentMethodId);

        $this->settingsRepository
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $this->configFactory
            ->method('create')
            ->will(
                static::returnValueMap(
                    [
                        [$settingsOneMock, $configOneMock],
                        [$settingsTwoMock, $configTwoMock]
                    ]
                )
            );

        $expectedResult = $configOneMock;
        $actualResult = $this->testedProvider->getPaymentConfig($onePaymentMethodId);

        static::assertEquals($expectedResult, $actualResult);
    }

    public function testGetPaymentConfigWhenNoSettings()
    {
        $this->settingsRepository
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        $actualResult = $this->testedProvider->getPaymentConfig('somePaymentMethodId');

        static::assertEquals(null, $actualResult);
    }

    public function testHasPaymentConfig()
    {
        $onePaymentMethodId = '1somePaymentMethodId';
        $twoPaymentMethodId = '2somePaymentMethodId';

        $settingsOneMock = $this->createMoneyOrderSettingsMock();
        $settingsTwoMock = $this->createMoneyOrderSettingsMock();

        $configOneMock = $this->createConfigMock();
        $configTwoMock = $this->createConfigMock();

        $settingsMocks = [$settingsOneMock, $settingsTwoMock];

        $configOneMock
            ->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($onePaymentMethodId);

        $configTwoMock
            ->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($twoPaymentMethodId);

        $this->settingsRepository
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $this->configFactory
            ->method('create')
            ->will(
                static::returnValueMap(
                    [
                        [$settingsOneMock, $configOneMock],
                        [$settingsTwoMock, $configTwoMock]
                    ]
                )
            );

        $expectedResult = true;
        $actualResult = $this->testedProvider->hasPaymentConfig($onePaymentMethodId);

        static::assertEquals($expectedResult, $actualResult);
    }

    public function testHasPaymentConfigWhenNoSettings()
    {
        $this->settingsRepository
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        $actualResult = $this->testedProvider->hasPaymentConfig('somePaymentMethodId');

        static::assertEquals(null, $actualResult);
    }
}
