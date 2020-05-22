<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodConfigRepository;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodConfigData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentMethodConfigRepositoryTest extends WebTestCase
{
    /**
     * @var PaymentMethodConfigRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(PaymentMethodConfig::class);

        $this->loadFixtures([
            LoadPaymentMethodConfigData::class,
        ]);
    }

    public function testFindByType()
    {
        $configs = $this->repository->findByType('money_order');

        $expectedConfigs = [
            $this->getReference('payment.payment_method_config.4'),
            $this->getReference('payment.payment_method_config.5'),
            $this->getReference('payment.payment_method_config.6'),
        ];
        foreach ($expectedConfigs as $expectedConfig) {
            $this->assertContains($expectedConfig, $configs);
        }
    }

    public function testFindByTypes()
    {
        $configs = $this->repository->findByType([
            'payment_term',
            'pay_pal_1',
        ]);

        $expectedConfigs = [
            $this->getReference('payment.payment_method_config.1'),
            $this->getReference('payment.payment_method_config.2'),
        ];
        foreach ($expectedConfigs as $expectedConfig) {
            $this->assertContains($expectedConfig, $configs);
        }
    }
}
