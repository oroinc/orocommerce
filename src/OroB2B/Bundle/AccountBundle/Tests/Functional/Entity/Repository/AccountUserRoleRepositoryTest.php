<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AccountUserRoleRepositoryTest extends WebTestCase
{
    /**
     * @var AccountUserRoleRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:AccountUserRole');

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData']);
    }

    public function testGetDefaultAccountUserRoleByWebsite()
    {
        /** @var AccountUserRole $role */
        $expectedRole = $this->getReference(LoadAccountUserRoleData::ROLE_WITH_WEBSITE);

        /** @var Website $website */
        $website = $expectedRole->getWebsites()->first();

        $role = $this->repository->getDefaultAccountUserRoleByWebsite($website);
        $this->assertEquals($expectedRole, $role);
    }

    public function testIsDefaultForWebsite()
    {
        /** @var AccountUserRole $role */
        $role = $this->getReference(LoadAccountUserRoleData::ROLE_WITH_WEBSITE);

        $isDefaultForWebsite = $this->repository->isDefaultForWebsite($role);
        $this->assertTrue($isDefaultForWebsite);
    }

    public function testHasAssignedUsers()
    {
        /** @var AccountUserRole $role */
        $role = $this->getReference(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER);

        $hasAssignedUsers = $this->repository->hasAssignedUsers($role);
        $this->assertTrue($hasAssignedUsers);
    }

    public function testRoleWithoutUserAndWebsite()
    {
        /** @var AccountUserRole $role */
        $role = $this->getReference(LoadAccountUserRoleData::ROLE_WITHOUT_USER_AND_WEBSITE);

        $hasAssignedUsers = $this->repository->hasAssignedUsers($role);
        $this->assertFalse($hasAssignedUsers);

        $isDefaultForWebsite = $this->repository->isDefaultForWebsite($role);
        $this->assertFalse($isDefaultForWebsite);
    }
}
