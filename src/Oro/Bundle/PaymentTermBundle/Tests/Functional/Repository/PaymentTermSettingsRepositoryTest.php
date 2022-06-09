<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Repository;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Entity\Repository\PaymentTermSettingsRepository;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PaymentTermSettingsRepositoryTest extends WebTestCase
{
    private PaymentTermSettingsRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadChannelData::class]);

        $this->repository = self::getContainer()->get('doctrine')
            ->getRepository(PaymentTermSettings::class);
    }

    public function testFindWithEnabledChannel()
    {
        $settingsByEnabledChannel = $this->repository->findWithEnabledChannel();

        self::assertContains($this->getReference('payment_term:transport_1'), $settingsByEnabledChannel);
        self::assertContains($this->getReference('payment_term:transport_2'), $settingsByEnabledChannel);
        self::assertNotContains($this->getReference('payment_term:transport_3'), $settingsByEnabledChannel);
    }
}
