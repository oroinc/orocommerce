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

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var PayPalSettings[]
     */
    protected $settings;

    /**
     * @var PayPalExpressCheckoutConfigProvider
     */
    protected $payPalConfigProvider;

    protected function setUp(): void
    {
        $this->type = 'paypal_payflow_gateway';

        $channel1 = $this->getEntity(Channel::class, ['id' => 1, 'type' => $this->type]);
        $channel2 = $this->getEntity(Channel::class, ['id' => 2, 'type' => $this->type]);

        $this->settings[] = $this->getEntity(PayPalSettings::class, ['id' => 1, 'channel' => $channel1]);
        $this->settings[] = $this->getEntity(PayPalSettings::class, ['id' => 2, 'channel' => $channel2]);

        $config = $this->createMock(PayPalExpressCheckoutConfig::class);
        $config->expects(static::at(0))
            ->method('getPaymentMethodIdentifier')
            ->willReturn('paypal_payments_pro_express_checkout_1');
        $config->expects(static::at(1))
            ->method('getPaymentMethodIdentifier')
            ->willReturn('paypal_payments_pro_express_checkout_2');

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $objectRepository = $this->createMock(PayPalSettingsRepository::class);
        $objectRepository->expects(static::once())
            ->method('getEnabledSettingsByType')
            ->with($this->type)
            ->willReturn($this->settings);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($objectRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        /** @var PayPalExpressCheckoutConfigFactory|\PHPUnit\Framework\MockObject\MockObject $factory */
        $factory = $this->createMock(PayPalExpressCheckoutConfigFactory::class);
        $factory->expects(static::exactly(2))
            ->method('createConfig')
            ->willReturn($config);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->payPalConfigProvider = new PayPalExpressCheckoutConfigProvider(
            $this->doctrine,
            $logger,
            $factory,
            $this->type
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
