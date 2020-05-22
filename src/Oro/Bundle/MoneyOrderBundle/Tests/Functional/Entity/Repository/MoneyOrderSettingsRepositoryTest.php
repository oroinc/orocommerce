<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\MoneyOrderBundle\Entity\Repository\MoneyOrderSettingsRepository;
use Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures\LoadMoneyOrderChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MoneyOrderSettingsRepositoryTest extends WebTestCase
{
    /**
     * @var MoneyOrderSettingsRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadMoneyOrderChannelData::class,
        ]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getManagerForClass('OroMoneyOrderBundle:MoneyOrderSettings')
            ->getRepository('OroMoneyOrderBundle:MoneyOrderSettings');
    }

    public function testGetEnabledSettings()
    {
        $settingsByEnabledChannel = $this->repository->findWithEnabledChannel();

        $fixtureSettingsByEnabledChannel = [
            $this->getReference('money_order:transport_1'),
            $this->getReference('money_order:transport_2')
        ];
        static::assertCount(count($fixtureSettingsByEnabledChannel), $settingsByEnabledChannel);
        static::assertEquals($fixtureSettingsByEnabledChannel, $settingsByEnabledChannel);
    }
}
