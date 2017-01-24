<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\MoneyOrderBundle\Entity\Repository\MoneyOrderSettingsRepository;
use Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures\LoadMoneyOrderChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class MoneyOrderSettingsRepositoryTest extends WebTestCase
{
    /**
     * @var MoneyOrderSettingsRepository
     */
    protected $repository;

    protected function setUp()
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
            $this->getReference('money_order:transport_1')
        ];
        static::assertCount(1, $settingsByEnabledChannel);
        static::assertEquals($fixtureSettingsByEnabledChannel, $settingsByEnabledChannel);
    }
}
