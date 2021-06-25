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

    private const TYPE = 'paypal_payments_pro';

    /** @var PayPalCreditCardConfigProvider */
    private $payPalConfigProvider;

    protected function setUp(): void
    {
        $channel1 = $this->getEntity(Channel::class, ['id' => 1, 'type' => self::TYPE]);
        $channel2 = $this->getEntity(Channel::class, ['id' => 2, 'type' => self::TYPE]);

        $config = $this->createMock(PayPalCreditCardConfig::class);
        $config->expects(self::exactly(2))
            ->method('getPaymentMethodIdentifier')
            ->willReturnOnConsecutiveCalls(
                'paypal_payments_pro_credit_card_1',
                'paypal_payments_pro_credit_card_2'
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

        $factory = $this->createMock(PayPalCreditCardConfigFactory::class);
        $factory->expects(self::exactly(2))
            ->method('createConfig')
            ->willReturn($config);

        $logger = $this->createMock(LoggerInterface::class);

        $this->payPalConfigProvider = new PayPalCreditCardConfigProvider(
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
