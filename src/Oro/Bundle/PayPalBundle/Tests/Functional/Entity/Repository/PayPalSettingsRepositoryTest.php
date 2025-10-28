<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Entity\Repository\PayPalSettingsRepository;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPayPalChannelData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class PayPalSettingsRepositoryTest extends WebTestCase
{
    private PayPalSettingsRepository $repository;

    #[\Override]
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
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $adminToken = new UsernamePasswordOrganizationToken(
            $this->getReference(LoadUser::USER),
            'key',
            $organization
        );

        $this->getContainer()->get('security.token_storage')->setToken($adminToken);

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
