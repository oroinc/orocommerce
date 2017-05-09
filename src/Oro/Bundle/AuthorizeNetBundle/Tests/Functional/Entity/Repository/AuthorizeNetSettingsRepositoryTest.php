<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\AuthorizeNetBundle\Entity\Repository\AuthorizeNetSettingsRepository;
use Oro\Bundle\AuthorizeNetBundle\Tests\Functional\DataFixtures\LoadAuthorizeNetChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AuthorizeNetSettingsRepositoryTest extends WebTestCase
{
    /**
     * @var AuthorizeNetSettingsRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadAuthorizeNetChannelData::class,
            ]
        );

        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(AuthorizeNetSettings::class)
            ->getRepository(AuthorizeNetSettings::class);
    }

    /**
     * @dataProvider getEnabledSettingsByTypeDataProvider
     *
     * @param string $type
     * @param integer $expectedCount
     */
    public function testGetEnabledSettingsByType($type, $expectedCount)
    {
        $enabledSettings = $this->repository->getEnabledSettingsByType($type);
        $this->assertCount($expectedCount, $enabledSettings);
    }

    /**
     * @return array
     */
    public function getEnabledSettingsByTypeDataProvider()
    {
        return [
            [
                'type' => 'authorize_net',
                'expectedCount' => 2,
            ],
        ];
    }
}
