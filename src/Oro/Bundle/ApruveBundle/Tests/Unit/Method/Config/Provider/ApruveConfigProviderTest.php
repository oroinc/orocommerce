<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Entity\Repository\ApruveSettingsRepository;
use Oro\Bundle\ApruveBundle\Method\Config\Factory\ApruveConfigFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProvider;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProviderInterface;

class ApruveConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    const IDENTIFIER1 = 'apruve_1';
    const IDENTIFIER2 = 'apruve_2';
    const WRONG_IDENTIFIER = 'wrongpayment_method';

    /**
     * @var ApruveConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configFactory;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var ApruveSettingsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsRepository;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var ApruveConfigProviderInterface
     */
    private $testedProvider;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->configFactory = $this->createMock(ApruveConfigFactoryInterface::class);
        $this->settingsRepository = $this->createMock(ApruveSettingsRepository::class);

        $settingsOneMock = $this->createApruveSettingsMock();
        $settingsTwoMock = $this->createApruveSettingsMock();

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

        $this->settingsRepository = $this->createMock(ApruveSettingsRepository::class);
        $this->settingsRepository
            ->expects(static::once())
            ->method('findEnabledSettings')
            ->willReturn($settingsMocks);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($this->settingsRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
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

        $this->testedProvider = new ApruveConfigProvider($this->doctrine, $logger, $this->configFactory);
    }

    /**
     * @return ApruveSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createApruveSettingsMock()
    {
        return $this->createMock(ApruveSettings::class);
    }

    /**
     * @return ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createConfigMock()
    {
        return $this->createMock(ApruveConfigInterface::class);
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
            ->method('findEnabledSettings')
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
            ->method('findEnabledSettings')
            ->willReturn([]);

        $actualResult = $this->testedProvider->hasPaymentConfig('somePaymentMethodId');

        static::assertEquals(null, $actualResult);
    }
}
