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
    const IDENTIFIER1 = 'payment_method_1';
    const IDENTIFIER2 = 'payment_method_2';

    /**
     * @var BasicPaymentTermConfigProvider
     */
    private $testedProvider;

    /**
     * @var PaymentTermConfigBySettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentTermConfigBySettingsFactoryMock;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrine;
    /**
     * @var PaymentTermSettingsRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentTermSettingsRepositoryMock;

    protected function setUp(): void
    {
        $this->paymentTermConfigBySettingsFactoryMock = $this->createMock(
            PaymentTermConfigBySettingsFactoryInterface::class
        );

        $settingsOneMock = $this->createPaymentTermSettingsMock();
        $settingsTwoMock = $this->createPaymentTermSettingsMock();

        $configOneMock = $this->createConfigMock();
        $configTwoMock = $this->createConfigMock();

        $settingsMocks = [$settingsOneMock, $settingsTwoMock];

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->paymentTermSettingsRepositoryMock = $this->createMock(PaymentTermSettingsRepository::class);
        $this->paymentTermSettingsRepositoryMock
            ->expects(static::once())
            ->method('findWithEnabledChannel')
            ->willReturn($settingsMocks);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())
            ->method('getRepository')->willReturn($this->paymentTermSettingsRepositoryMock);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

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
            $this->doctrine,
            $logger,
            $this->paymentTermConfigBySettingsFactoryMock
        );
    }

    /**
     * @return PaymentTermSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPaymentTermSettingsMock()
    {
        return $this->createMock(PaymentTermSettings::class);
    }

    /**
     * @return PaymentTermConfigInterface|\PHPUnit\Framework\MockObject\MockObject
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
