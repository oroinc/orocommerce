<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Entity\Repository\PaymentTermSettingsRepository;
use Oro\Bundle\PaymentTermBundle\Method\Config\Factory\Settings\PaymentTermConfigBySettingsFactoryInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Basic\BasicPaymentTermConfigProvider;
use Psr\Log\LoggerInterface;

class BasicPaymentTermConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER1 = 'payment_method_1';
    private const IDENTIFIER2 = 'payment_method_2';

    /** @var PaymentTermSettingsRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermSettingsRepository;

    /** @var BasicPaymentTermConfigProvider */
    private $testedProvider;

    /** @var array */
    private $configs;

    protected function setUp(): void
    {
        $settingsOne = $this->createMock(PaymentTermSettings::class);
        $settingsTwo = $this->createMock(PaymentTermSettings::class);

        $configOne = $this->createMock(PaymentTermConfigInterface::class);
        $configOne->expects(self::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn(self::IDENTIFIER1);

        $configTwo = $this->createMock(PaymentTermConfigInterface::class);
        $configTwo->expects(self::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn(self::IDENTIFIER2);

        $this->paymentTermSettingsRepository = $this->createMock(PaymentTermSettingsRepository::class);
        $this->paymentTermSettingsRepository->expects(self::once())
            ->method('findWithEnabledChannel')
            ->willReturn([$settingsOne, $settingsTwo]);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->paymentTermSettingsRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $logger = $this->createMock(LoggerInterface::class);

        $this->paymentTermSettingsRepository->expects(self::once())
            ->method('findWithEnabledChannel')
            ->willReturn([$settingsOne, $settingsTwo]);

        $paymentTermConfigBySettingsFactory = $this->createMock(PaymentTermConfigBySettingsFactoryInterface::class);
        $paymentTermConfigBySettingsFactory->expects(self::any())
            ->method('createConfigBySettings')
            ->willReturnMap([
                [$settingsOne, $configOne],
                [$settingsTwo, $configTwo]
            ]);
        $this->configs = [
            self::IDENTIFIER1 => $configOne,
            self::IDENTIFIER2 => $configTwo
        ];

        $this->testedProvider = new BasicPaymentTermConfigProvider(
            $doctrine,
            $logger,
            $paymentTermConfigBySettingsFactory
        );
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
        $this->paymentTermSettingsRepository->expects(self::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        self::assertNull($this->testedProvider->getPaymentConfig('somePaymentMethodId'));
    }

    public function testHasPaymentConfig()
    {
        self::assertTrue($this->testedProvider->hasPaymentConfig(self::IDENTIFIER1));
    }

    public function testHasPaymentConfigWhenNoSettings()
    {
        $this->paymentTermSettingsRepository->expects(self::once())
            ->method('findWithEnabledChannel')
            ->willReturn([]);

        self::assertFalse($this->testedProvider->hasPaymentConfig('somePaymentMethodId'));
    }
}
