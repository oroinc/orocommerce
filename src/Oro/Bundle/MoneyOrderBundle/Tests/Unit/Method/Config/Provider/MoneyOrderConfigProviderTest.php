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
    private const IDENTIFIER1 = 'payment_method_1';
    private const IDENTIFIER2 = 'payment_method_2';
    private const WRONG_IDENTIFIER = 'wrongpayment_method';

    /** @var MoneyOrderSettingsRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $settingsRepository;

    /** @var array */
    private $configs;

    /** @var MoneyOrderConfigProviderInterface */
    private $testedProvider;

    protected function setUp(): void
    {
        $this->settingsRepository = $this->createMock(MoneyOrderSettingsRepository::class);

        $settingsOne = $this->createMock(MoneyOrderSettings::class);
        $settingsTwo = $this->createMock(MoneyOrderSettings::class);

        $configOne = $this->createMock(MoneyOrderConfigInterface::class);
        $configOne->expects(self::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn(self::IDENTIFIER1);

        $configTwo = $this->createMock(MoneyOrderConfigInterface::class);
        $configTwo->expects(self::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn(self::IDENTIFIER2);

        $this->settingsRepository = $this->createMock(MoneyOrderSettingsRepository::class);
        $this->settingsRepository->expects(self::once())
            ->method('findWithEnabledChannel')
            ->willReturn([$settingsOne, $settingsTwo]);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->settingsRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $logger = $this->createMock(LoggerInterface::class);

        $configFactory = $this->createMock(MoneyOrderConfigFactoryInterface::class);
        $configFactory->expects(self::any())
            ->method('create')
            ->willReturnMap([
                [$settingsOne, $configOne],
                [$settingsTwo, $configTwo]
            ]);

        $this->configs = [
            self::IDENTIFIER1 => $configOne,
            self::IDENTIFIER2 => $configTwo
        ];

        $this->testedProvider = new MoneyOrderConfigProvider($doctrine, $logger, $configFactory);
    }

    public function testGetPaymentConfigs()
    {
        self::assertEquals($this->configs, $this->testedProvider->getPaymentConfigs());
    }

    public function testGetPaymentConfig()
    {
        self::assertEquals(
            $this->configs[self::IDENTIFIER1],
            $this->testedProvider->getPaymentConfig(self::IDENTIFIER1)
        );
    }

    public function testGetPaymentConfigWhenNoSettings()
    {
        $this->settingsRepository->expects(self::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        self::assertNull($this->testedProvider->getPaymentConfig(self::WRONG_IDENTIFIER));
    }

    public function testHasPaymentConfig()
    {
        self::assertTrue($this->testedProvider->hasPaymentConfig(self::IDENTIFIER1));
    }

    public function testHasPaymentConfigWhenNoSettings()
    {
        $this->settingsRepository->expects(self::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        self::assertNull(null, $this->testedProvider->hasPaymentConfig('somePaymentMethodId'));
    }
}
