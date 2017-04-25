<?php

namespace Oro\Bundle\ApruveBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Entity\Repository\ApruveSettingsRepository;
use Oro\Bundle\ApruveBundle\Tests\Functional\DataFixtures\LoadApruveChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ApruveSettingsRepositoryTest extends WebTestCase
{
    /**
     * @var ApruveSettingsRepository
     */
    private $repository;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadApruveChannelData::class,
        ]);

        $this->repository = static::getContainer()
            ->get('doctrine')
            ->getManagerForClass(ApruveSettings::class)
            ->getRepository(ApruveSettings::class);
    }

    public function testFindEnabledSettings()
    {
        $settingsByEnabledChannel = $this->repository->findEnabledSettings();

        $fixtureSettingsByEnabledChannel = [
            $this->getReference('apruve:transport_1'),
            $this->getReference('apruve:transport_2')
        ];
        static::assertCount(count($fixtureSettingsByEnabledChannel), $settingsByEnabledChannel);
        static::assertEquals($fixtureSettingsByEnabledChannel, $settingsByEnabledChannel);
    }
}
