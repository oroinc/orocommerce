<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Entity\Repository\PayPalSettingsRepository;
use Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalExpressCheckoutConfigFactory;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class PayPalExpressCheckoutConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const TYPE = 'paypal_payflow_gateway';

    /** @var PayPalExpressCheckoutConfigProvider */
    private $payPalConfigProvider;

    protected function setUp(): void
    {
        $channel1 = $this->getEntity(Channel::class, ['id' => 1, 'type' => self::TYPE]);
        $channel2 = $this->getEntity(Channel::class, ['id' => 2, 'type' => self::TYPE]);

        $config = $this->createMock(PayPalExpressCheckoutConfig::class);
        $config->expects(self::exactly(2))
            ->method('getPaymentMethodIdentifier')
            ->willReturnOnConsecutiveCalls(
                'paypal_payments_pro_express_checkout_1',
                'paypal_payments_pro_express_checkout_2'
            );

        $objectRepository = $this->createMock(PayPalSettingsRepository::class);
        $objectRepository->expects(self::once())
            ->method('getEnabledSettingsByType')
            ->with(self::TYPE)
            ->willReturn([
                $this->getEntity(PayPalSettings::class, ['id' => 1, 'channel' => $channel1]),
                $this->getEntity(PayPalSettings::class, ['id' => 2, 'channel' => $channel2])
            ]);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($objectRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $factory = $this->createMock(PayPalExpressCheckoutConfigFactory::class);
        $factory->expects(self::exactly(2))
            ->method('createConfig')
            ->willReturn($config);

        $logger = $this->createMock(LoggerInterface::class);

        $this->payPalConfigProvider = new PayPalExpressCheckoutConfigProvider(
            $doctrine,
            $logger,
            $factory,
            self::TYPE
        );
    }

    public function testGetPaymentConfigs()
    {
        $this->assertCount(2, $this->payPalConfigProvider->getPaymentConfigs());
    }

    public function testGetPaymentConfig()
    {
        $this->assertInstanceOf(
            PayPalExpressCheckoutConfig::class,
            $this->payPalConfigProvider->getPaymentConfig('paypal_payments_pro_express_checkout_1')
        );
    }

    public function testHasPaymentConfig()
    {
        $this->assertTrue($this->payPalConfigProvider->hasPaymentConfig('paypal_payments_pro_express_checkout_2'));
    }
}
