<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Repository;

use Oro\Bundle\PaymentTermBundle\Entity\Repository\PaymentTermSettingsRepository;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PaymentTermSettingsRepositoryTest extends WebTestCase
{
    /**
     * @var PaymentTermSettingsRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadChannelData::class,
            ]
        );

        $this->repository = static::getContainer()
            ->get('doctrine')
            ->getRepository('OroPaymentTermBundle:PaymentTermSettings');
    }

    public function testFindWithEnabledChannel()
    {
        $settingsByEnabledChannel = $this->repository->findWithEnabledChannel();

        static::assertTrue(in_array($this->getReference('payment_term:transport_1'), $settingsByEnabledChannel, true));
        static::assertTrue(in_array($this->getReference('payment_term:transport_2'), $settingsByEnabledChannel, true));
        static::assertFalse(in_array($this->getReference('payment_term:transport_3'), $settingsByEnabledChannel, true));
    }
}
