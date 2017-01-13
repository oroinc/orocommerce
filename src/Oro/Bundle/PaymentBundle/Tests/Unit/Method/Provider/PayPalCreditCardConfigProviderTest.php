<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PaymentBundle\Method\Provider\PayPalConfigProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PayPalCreditCardConfigProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PayPalCreditCardConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $encoder;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var PayPalConfigProvider
     */
    protected $payPalConfigProvider;

    protected function setUp()
    {
        $this->type = 'paypal_payments_pro';
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);
        $this->payPalConfigProvider = new PayPalCreditCardConfigProvider($this->doctrine, $this->encoder, $this->type);
    }

    public function testGetPaymentConfigs()
    {
        $channels = [];
        $channels[] = $this->getEntity(Channel::class, ['id' => 1, 'type' => 'paypal_payflow_gateway']);
        $channels[] = $this->getEntity(Channel::class, ['id' => 2, 'type' => 'paypal_payments_pro']);

        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects(static::once())->method('findBy')->willReturn($channels);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($objectRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        $this->assertCount(2, $this->payPalConfigProvider->getPaymentConfigs());
    }

    public function testGetPaymentConfig()
    {
        $identifier = 'paypal_payflow_gateway_credit_card_1';

        $channels = [];
        $channels[] = $this->getEntity(Channel::class, ['id' => 1, 'type' => 'paypal_payflow_gateway']);
        $channels[] = $this->getEntity(Channel::class, ['id' => 2, 'type' => 'paypal_payments_pro']);

        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects(static::once())->method('findBy')->willReturn($channels);

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

        $channels = [];
        $channels[] = $this->getEntity(Channel::class, ['id' => 1, 'type' => 'paypal_payflow_gateway']);
        $channels[] = $this->getEntity(Channel::class, ['id' => 2, 'type' => 'paypal_payments_pro']);

        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects(static::once())->method('findBy')->willReturn($channels);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::once())->method('getRepository')->willReturn($objectRepository);

        $this->doctrine->expects(static::once())->method('getManagerForClass')->willReturn($objectManager);

        $this->assertTrue($this->payPalConfigProvider->hasPaymentConfig($identifier));
    }
}
