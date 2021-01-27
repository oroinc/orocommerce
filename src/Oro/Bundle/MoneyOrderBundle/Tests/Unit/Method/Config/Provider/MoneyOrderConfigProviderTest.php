<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Entity\Repository\MoneyOrderSettingsRepository;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactoryInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProvider;
use Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProviderInterface;
use Psr\Log\LoggerInterface;

class MoneyOrderConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    const IDENTIFIER1 = 'payment_method_1';
    const IDENTIFIER2 = 'payment_method_2';
    const WRONG_IDENTIFIER = 'wrongpayment_method';

    /**
     * @var MoneyOrderConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configFactory;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrine;

    /**
     * @var MoneyOrderSettingsRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $settingsRepository;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var MoneyOrderConfigProviderInterface
     */
    private $testedProvider;

    protected function setUp(): void
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

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->settingsRepository = $this->createMock(MoneyOrderSettingsRepository::class);
        $this->settingsRepository
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($this->settingsRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

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

        $this->testedProvider = new MoneyOrderConfigProvider($this->doctrine, $logger, $this->configFactory);
    }

    /**
     * @return MoneyOrderSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMoneyOrderSettingsMock()
    {
        return $this->createMock(MoneyOrderSettings::class);
    }

    /**
     * @return MoneyOrderConfigInterface|\PHPUnit\Framework\MockObject\MockObject
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
