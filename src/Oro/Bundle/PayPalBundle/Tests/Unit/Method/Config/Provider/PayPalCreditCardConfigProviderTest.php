<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Method\Config\Builder\Factory\PayPalCreditCardConfigFactory;
use Oro\Bundle\PayPalBundle\Method\Config\Builder\PayPalCreditCardConfigBuilder;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class PayPalCreditCardConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var Channel[]
     */
    protected $channels;

    /**
     * @var PayPalCreditCardConfigProvider
     */
    protected $payPalConfigProvider;

    protected function setUp()
    {
        $this->type = 'paypal_payments_pro';

        $this->channels = [];
        $this->channels[] = $this->getEntity(Channel::class, ['id' => 1, 'type' => 'paypal_payflow_gateway']);
        $this->channels[] = $this->getEntity(Channel::class, ['id' => 2, 'type' => 'paypal_payments_pro']);

        $config = $this->createMock(PayPalCreditCardConfig::class);
        $config->expects(static::at(0))
            ->method('getPaymentMethodIdentifier')
            ->willReturn('paypal_payflow_gateway_credit_card_1');
        $config->expects(static::at(1))
            ->method('getPaymentMethodIdentifier')
            ->willReturn('paypal_payments_pro_credit_card_2');

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $builder = $this->createMock(PayPalCreditCardConfigBuilder::class);
        $builder->expects(static::exactly(2))
            ->method('setChannel')
            ->willReturnSelf();

        $builder->expects(static::exactly(2))
            ->method('getResult')
            ->willReturn($config);
        
        /** @var PayPalCreditCardConfigFactory|\PHPUnit_Framework_MockObject_MockObject $factory */
        $factory = $this->createMock(PayPalCreditCardConfigFactory::class);
        $factory->expects(static::once())
            ->method('createPayPalConfigBuilder')
            ->willReturn($builder);
        
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
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
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects(static::once())->method('findBy')->willReturn($this->channels);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($objectRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        $this->assertCount(2, $this->payPalConfigProvider->getPaymentConfigs());
    }

    public function testGetPaymentConfig()
    {
        $identifier = 'paypal_payflow_gateway_credit_card_1';

        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects(static::once())->method('findBy')->willReturn($this->channels);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($objectRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        $this->assertInstanceOf(
            PayPalCreditCardConfig::class,
            $this->payPalConfigProvider->getPaymentConfig($identifier)
        );
    }

    public function testHasPaymentConfig()
    {
        $identifier = 'paypal_payments_pro_credit_card_2';

        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects(static::once())->method('findBy')->willReturn($this->channels);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($objectRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        $this->assertTrue($this->payPalConfigProvider->hasPaymentConfig($identifier));
    }
}
