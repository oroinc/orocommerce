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
    const IDENTIFIER1 = 'payment_method_1';
    const IDENTIFIER2 = 'payment_method_2';
    const WRONG_IDENTIFIER = 'wrongpayment_method';

    /**
     * @var MoneyOrderConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configFactory;

    /**
     * @var MoneyOrderSettingsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $settingsRepository;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var MoneyOrderConfigProviderInterface
     */
    private $testedProvider;

    public function setUp()
    {
        $this->configFactory = $this->createMock(MoneyOrderConfigFactoryInterface::class);
        $this->settingsRepository = $this->createMock(MoneyOrderSettingsRepository::class);

        $settingsOneMock = $this->createMoneyOrderSettingsMock();
        $settingsTwoMock = $this->createMoneyOrderSettingsMock();

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
        $this->configs = [
            self::IDENTIFIER1 => $configOneMock,
            self::IDENTIFIER2 => $configTwoMock
        ];

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
        $this->settingsRepository
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        $actualResult = $this->testedProvider->getPaymentConfig(self::WRONG_IDENTIFIER);

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
        $this->settingsRepository
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        $actualResult = $this->testedProvider->hasPaymentConfig('somePaymentMethodId');

        static::assertEquals(null, $actualResult);
    }
}
