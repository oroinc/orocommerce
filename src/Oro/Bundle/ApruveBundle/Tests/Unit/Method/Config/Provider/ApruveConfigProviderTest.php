<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Entity\Repository\ApruveSettingsRepository;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Config\Factory\ApruveConfigFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProvider;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProviderInterface;
use Psr\Log\LoggerInterface;

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
    private $doctrine;

    /**
     * @var ApruveSettingsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $settingsRepository;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var ApruveConfigProviderInterface
     */
    private $testedProvider;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

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
            ->method('getPaymentMethodIdentifier')
            ->willReturn(self::IDENTIFIER1);

        $configTwoMock
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

        $this->logger = $this->createMock(LoggerInterface::class);

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

        $this->testedProvider = new ApruveConfigProvider($this->doctrine, $this->logger, $this->configFactory);
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

        static::assertSame($this->configs, $actualResult);
    }

    public function testGetPaymentConfig()
    {
        $expectedResult = $this->configs[self::IDENTIFIER1];
        $actualResult = $this->testedProvider->getPaymentConfig(self::IDENTIFIER1);

        static::assertSame($expectedResult, $actualResult);
    }

    public function testGetPaymentConfigWhenNoSettings()
    {
        $this->settingsRepository
            ->expects(static::once())
            ->method('findEnabledSettings')
            ->willReturn([]);

        $actualResult = $this->testedProvider->getPaymentConfig(self::WRONG_IDENTIFIER);

        static::assertNull($actualResult);
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

        static::assertFalse($actualResult);
    }

    public function testHasPaymentConfigWithException()
    {
        $this->settingsRepository
            ->expects(static::once())
            ->method('findEnabledSettings')
            ->willThrowException(new \UnexpectedValueException());

        $this->logger
            ->expects(static::once())
            ->method('error');

        $actualResult = $this->testedProvider->hasPaymentConfig('somePaymentMethodId');

        static::assertFalse($actualResult);
    }
}
