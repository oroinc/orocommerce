<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Entity\Repository\PayPalSettingsRepository;
use Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalCreditCardConfigFactory;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class PayPalCreditCardConfigProviderTest extends \PHPUnit\Framework\TestCase
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
     * @var PayPalCreditCardConfigProvider
     */
    protected $payPalConfigProvider;

    protected function setUp(): void
    {
        $this->type = 'paypal_payments_pro';

        $channel1 = $this->getEntity(Channel::class, ['id' => 1, 'type' => $this->type]);
        $channel2 = $this->getEntity(Channel::class, ['id' => 2, 'type' => $this->type]);

        $this->settings[] = $this->getEntity(PayPalSettings::class, ['id' => 1, 'channel' => $channel1]);
        $this->settings[] = $this->getEntity(PayPalSettings::class, ['id' => 2, 'channel' => $channel2]);

        $config = $this->createMock(PayPalCreditCardConfig::class);
        $config->expects(static::at(0))
            ->method('getPaymentMethodIdentifier')
            ->willReturn('paypal_payments_pro_credit_card_1');
        $config->expects(static::at(1))
            ->method('getPaymentMethodIdentifier')
            ->willReturn('paypal_payments_pro_credit_card_2');

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $objectRepository = $this->createMock(PayPalSettingsRepository::class);
        $objectRepository->expects(static::once())
            ->method('getEnabledSettingsByType')
            ->with($this->type)
            ->willReturn($this->settings);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($objectRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        /** @var PayPalCreditCardConfigFactory|\PHPUnit\Framework\MockObject\MockObject $factory */
        $factory = $this->createMock(PayPalCreditCardConfigFactory::class);
        $factory->expects(static::exactly(2))
            ->method('createConfig')
            ->willReturn($config);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->payPalConfigProvider = new PayPalCreditCardConfigProvider(
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
        $identifier = 'paypal_payments_pro_credit_card_1';

        $this->assertInstanceOf(
            PayPalCreditCardConfig::class,
            $this->payPalConfigProvider->getPaymentConfig($identifier)
        );
    }

    public function testHasPaymentConfig()
    {
        $identifier = 'paypal_payments_pro_credit_card_2';

        $this->assertTrue($this->payPalConfigProvider->hasPaymentConfig($identifier));
    }
}
