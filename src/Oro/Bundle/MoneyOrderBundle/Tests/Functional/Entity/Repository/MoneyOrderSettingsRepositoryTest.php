<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Entity\Repository\MoneyOrderSettingsRepository;
use Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures\LoadMoneyOrderChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MoneyOrderSettingsRepositoryTest extends WebTestCase
{
    private MoneyOrderSettingsRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadMoneyOrderChannelData::class]);

        $this->repository = self::getContainer()->get('doctrine')
            ->getRepository(MoneyOrderSettings::class);
    }

    public function testGetEnabledSettings()
    {
        $settingsByEnabledChannel = $this->repository->findWithEnabledChannel();

        $fixtureSettingsByEnabledChannel = [
            $this->getReference('money_order:transport_1'),
            $this->getReference('money_order:transport_2')
        ];
        self::assertCount(count($fixtureSettingsByEnabledChannel), $settingsByEnabledChannel);
        self::assertEquals($fixtureSettingsByEnabledChannel, $settingsByEnabledChannel);
    }
}
