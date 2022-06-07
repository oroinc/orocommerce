<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Entity\Repository\PayPalSettingsRepository;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPayPalChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PayPalSettingsRepositoryTest extends WebTestCase
{
    private PayPalSettingsRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadPayPalChannelData::class]);

        $this->repository = self::getContainer()->get('doctrine')
            ->getRepository(PayPalSettings::class);
    }

    /**
     * @dataProvider getEnabledSettingsByTypeDataProvider
     */
    public function testGetEnabledSettingsByType(string $type, int $expectedCount)
    {
        $enabledSettings = $this->repository->getEnabledSettingsByType($type);
        $this->assertCount($expectedCount, $enabledSettings);
    }

    public function getEnabledSettingsByTypeDataProvider(): array
    {
        return [
            [
                'type' => 'paypal_payflow_gateway',
                'expectedCount' => 1
            ],
            [
                'type' => 'paypal_payments_pro',
                'expectedCount' => 2
            ],
        ];
    }
}
