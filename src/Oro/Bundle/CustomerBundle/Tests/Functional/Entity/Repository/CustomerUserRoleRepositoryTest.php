<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserRoleData;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class CustomerUserRoleRepositoryTest extends WebTestCase
{
    /**
     * @var CustomerUserRoleRepository
     */
    protected $repository;

    /**
     * @var int
     */
    protected static $defaultRolesCount;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCustomerBundle:CustomerUserRole');

        if (null === self::$defaultRolesCount) {
            self::$defaultRolesCount = (int)$this->repository->createQueryBuilder('r')
                ->select('count(r)')
                ->getQuery()
                ->getSingleScalarResult();
        }
        $this->loadFixtures(['Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserRoleData']);
    }

    public function testGetDefaultCustomerUserRoleByWebsite()
    {
        /** @var CustomerUserRole $role */
        $expectedRole = $this->getReference(LoadCustomerUserRoleData::ROLE_WITH_WEBSITE);

        /** @var Website $website */
        $website = $expectedRole->getWebsites()->first();

        $role = $this->repository->getDefaultCustomerUserRoleByWebsite($website);
        $this->assertEquals($expectedRole, $role);
    }

    public function testIsDefaultForWebsite()
    {
        /** @var CustomerUserRole $role */
        $role = $this->getReference(LoadCustomerUserRoleData::ROLE_WITH_WEBSITE);

        $isDefaultForWebsite = $this->repository->isDefaultForWebsite($role);
        $this->assertTrue($isDefaultForWebsite);
    }

    public function testHasAssignedUsers()
    {
        /** @var CustomerUserRole $role */
        $role = $this->getReference(LoadCustomerUserRoleData::ROLE_WITH_ACCOUNT_USER);

        $hasAssignedUsers = $this->repository->hasAssignedUsers($role);
        $this->assertTrue($hasAssignedUsers);
    }

    public function testGetAssignedUsers()
    {
        /** @var CustomerUserRole $role */
        $role = $this->getReference(LoadCustomerUserRoleData::ROLE_WITH_ACCOUNT_USER);
        $assignedUsers = $this->repository->getAssignedUsers($role);
        $expectedUsers = [
            $this->getReference(LoadAccountUserData::EMAIL)
        ];

        $this->assertEquals($expectedUsers, $assignedUsers);
    }

    public function testRoleWithoutUserAndWebsite()
    {
        /** @var CustomerUserRole $role */
        $role = $this->getReference(LoadCustomerUserRoleData::ROLE_EMPTY);

        $hasAssignedUsers = $this->repository->hasAssignedUsers($role);
        $this->assertFalse($hasAssignedUsers);

        $isDefaultForWebsite = $this->repository->isDefaultForWebsite($role);
        $this->assertFalse($isDefaultForWebsite);
    }

    /**
     * @dataProvider customerUserRolesDataProvider
     * @param string $accountUser
     * @param array $expectedCustomerUserRoles
     */
    public function testGetAvailableRolesByAccountUserQueryBuilder($accountUser, array $expectedCustomerUserRoles)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getReference($accountUser);
        /** @var CustomerUserRole[] $actual */
        $actual = $this->repository
            ->getAvailableRolesByAccountUserQueryBuilder(
                $accountUser->getOrganization(),
                $accountUser->getAccount()
            )
            ->getQuery()
            ->getResult();
        $this->assertCount(count($expectedCustomerUserRoles) +  self::$defaultRolesCount, $actual);
        $roleIds = [];
        foreach ($actual as $role) {
            $roleIds[] = $role->getId();
        }
        foreach ($expectedCustomerUserRoles as $roleReference) {
            $this->assertContains($this->getReference($roleReference)->getId(), $roleIds);
        }
    }

    /**
     * @dataProvider customerUserRolesDataProvider
     * @param string $accountUser
     */
    public function testGetAvailableSelfManagedRolesByAccountUserQueryBuilder(
        $accountUser
    ) {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getReference($accountUser);
        /** @var CustomerUserRole[] $actual */
        $actual = $this->repository
            ->getAvailableSelfManagedRolesByAccountUserQueryBuilder(
                $accountUser->getOrganization(),
                $accountUser->getAccount()
            )
            ->getQuery()
            ->getResult();

        $roleIds = [];

        foreach ($actual as $role) {
            $roleIds[] = $role->getId();
        }

        $this->assertNotContains(
            $this->getReference(LoadCustomerUserRoleData::ROLE_NOT_SELF_MANAGED)->getId(),
            $roleIds
        );
    }

    /**
     * @return array
     */
    public function customerUserRolesDataProvider()
    {
        return [
            'user from account with custom role' => [
                'grzegorz.brzeczyszczykiewicz@example.com',
                [
                    LoadCustomerUserRoleData::ROLE_WITH_ACCOUNT,
                    LoadCustomerUserRoleData::ROLE_WITH_ACCOUNT_USER,
                    LoadCustomerUserRoleData::ROLE_WITH_WEBSITE,
                    LoadCustomerUserRoleData::ROLE_EMPTY,
                    LoadCustomerUserRoleData::ROLE_NOT_SELF_MANAGED,
                    LoadCustomerUserRoleData::ROLE_SELF_MANAGED,
                    LoadCustomerUserRoleData::ROLE_NOT_PUBLIC,
                ]
            ],
            'user from account without custom roles' => [
                'orphan.user@test.com',
                [
                    LoadCustomerUserRoleData::ROLE_WITH_ACCOUNT_USER,
                    LoadCustomerUserRoleData::ROLE_WITH_WEBSITE,
                    LoadCustomerUserRoleData::ROLE_EMPTY,
                    LoadCustomerUserRoleData::ROLE_NOT_SELF_MANAGED,
                    LoadCustomerUserRoleData::ROLE_SELF_MANAGED,
                    LoadCustomerUserRoleData::ROLE_NOT_PUBLIC,
                ]
            ]
        ];
    }
}
