<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\CustomerBundle\Entity\Repository\AccountUserRoleRepository;
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
            ->getRepository('OroB2BCustomerBundle:AccountUserRole');

        $this->loadFixtures(['OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData']);
    }

    public function testGetDefaultAccountUserRoleByWebsite()
    {
        /** @var AccountUserRole $role */
        $expectedRole = $this->getReference('Role with website');

        /** @var Website $website */
        $website = $expectedRole->getWebsites()->first();

        $role = $this->repository->getDefaultAccountUserRoleByWebsite($website);
        $this->assertEquals($expectedRole, $role);
    }

    public function testIsDefaultForWebsite()
    {
        /** @var AccountUserRole $role */
        $role = $this->getReference('Role with website');

        $isDefaultForWebsite = $this->repository->isDefaultForWebsite($role);
        $this->assertEquals(true, $isDefaultForWebsite);
    }

    public function testHasAssignedUsers()
    {
        /** @var AccountUserRole $role */
        $role = $this->getReference('Role with account user');

        $hasAssignedUsers = $this->repository->hasAssignedUsers($role);
        $this->assertEquals(true, $hasAssignedUsers);
    }

    public function testRoleWithoutUserAndWebsite()
    {
        /** @var AccountUserRole $role */
        $role = $this->getReference('Role without user and website');

        $hasAssignedUsers = $this->repository->hasAssignedUsers($role);
        $this->assertEquals(false, $hasAssignedUsers);

        $isDefaultForWebsite = $this->repository->isDefaultForWebsite($role);
        $this->assertEquals(false, $isDefaultForWebsite);
    }
}
