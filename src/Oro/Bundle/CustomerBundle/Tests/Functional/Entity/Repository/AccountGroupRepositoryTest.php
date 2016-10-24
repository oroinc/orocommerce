<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\Repository\AccountGroupRepository;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAnonymousAccountGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AccountGroupRepositoryTest extends WebTestCase
{
    /**
     * @var AccountGroupRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCustomerBundle:AccountGroup');

        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups',
                'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            ]
        );
    }


    public function testGetBatchIterator()
    {
        $expectedNames = [
            LoadAnonymousAccountGroup::GROUP_NAME_NON_AUTHENTICATED,
            LoadGroups::GROUP1,
            LoadGroups::GROUP2,
            LoadGroups::GROUP3,
        ];

        $accountGroupsIterator = $this->repository->getBatchIterator();
        $accountGroupNames = [];
        foreach ($accountGroupsIterator as $accountGroup) {
            $accountGroupNames[] = $accountGroup->getName();
        }

        $this->assertEquals($expectedNames, $accountGroupNames);
    }
}
