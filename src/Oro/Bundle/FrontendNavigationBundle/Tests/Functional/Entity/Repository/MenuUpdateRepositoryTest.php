<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\FrontendNavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData;
use Oro\Bundle\FrontendNavigationBundle\Entity\Repository\MenuUpdateRepository;

/**
 * @dbIsolation
 */
class MenuUpdateRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var MenuUpdateRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository('OroFrontendNavigationBundle:MenuUpdate');

        $this->loadFixtures(
            [
                'Oro\Bundle\FrontendNavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData',
            ]
        );
    }

    /**
     * @dataProvider getUpdatesProvider
     *
     * @param int $expectedCount
     * @param bool $useOrganizationScope
     * @param bool $useAccountScope
     * @param bool $useAccountUserScope
     */
    public function testGetUpdates(
        $expectedCount,
        $useOrganizationScope = false,
        $useAccountScope = false,
        $useAccountUserScope = false
    ) {
        $updates = $this->repository->getUpdates(
            LoadMenuUpdateData::MENU,
            $useOrganizationScope ? $this->getReference(LoadMenuUpdateData::ORGANIZATION) : null,
            $useAccountScope ? $this->getReference(LoadMenuUpdateData::ACCOUNT) : null,
            $useAccountUserScope ? $this->getReference(LoadAccountUserData::EMAIL) : null,
            $this->getReference(LoadWebsiteData::WEBSITE1)
        );
        $this->assertCount($expectedCount, $updates);
    }

    /**
     * @return array
     */
    public function getUpdatesProvider()
    {
        return [
            'global scope' => [
                'expectedCount' => 1,
            ],
            'organization scope' => [
                'expectedCount' => 2,
                'useOrganizationScope' => true,
            ],
            'account scope' => [
                'expectedCount' => 2,
                'useOrganizationScope' => false,
                'useAccountScope' => true,
                'useAccountUserScope' => false,
            ],
            'account user scope' => [
                'expectedCount' => 2,
                'useOrganizationScope' => false,
                'useAccountScope' => false,
                'useAccountUserScope' => true,
            ],
            'all scopes' => [
                'expectedCount' => 4,
                'useOrganizationScope' => true,
                'useAccountScope' => true,
                'useAccountUserScope' => true,
            ],
        ];
    }

    /**
     * @dataProvider getUpdatesShouldFailProvider
     * @expectedException \BadMethodCallException
     *
     * @param bool $useAccountScope
     * @param bool $useAccountUserScope
     */
    public function testGetUpdatesShouldFail(
        $useAccountScope = false,
        $useAccountUserScope = false
    ) {
        $this->repository->getUpdates(
            LoadMenuUpdateData::MENU,
            null,
            $useAccountScope ? $this->getReference(LoadMenuUpdateData::ACCOUNT) : null,
            $useAccountUserScope ? $this->getReference(LoadAccountUserData::EMAIL) : null
        );
    }

    /**
     * @return array
     */
    public function getUpdatesShouldFailProvider()
    {
        return [
            'account scope' => [
                'useAccountScope' => true,
            ],
            'account user scope' => [
                'useAccountScope' => false,
                'useAccountUserScope' => true,
            ]
        ];
    }
}
