<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
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

    /**
     * @var int
     */
    protected static $defaultRolesCount;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:AccountUserRole');

        if (null === self::$defaultRolesCount) {
            self::$defaultRolesCount = (int)$this->repository->createQueryBuilder('r')
                ->select('count(r)')
                ->getQuery()
                ->getSingleScalarResult();
        }
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

    public function testGetAssignedUsers()
    {
        /** @var AccountUserRole $role */
        $role = $this->getReference(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER);
        $assignedUsers = $this->repository->getAssignedUsers($role);
        $expectedUsers = [
            $this->getReference(LoadAccountUserData::EMAIL)
        ];

        $this->assertEquals($expectedUsers, $assignedUsers);
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

    /**
     * @dataProvider accountUserRolesDataProvider
     * @param string $accountUser
     * @param array $expectedAccountUserRoles
     */
    public function testGetAvailableRolesByAccountUserQueryBuilder($accountUser, array $expectedAccountUserRoles)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getReference($accountUser);
        /** @var AccountUserRole[] $actual */
        $actual = $this->repository
            ->getAvailableRolesByAccountUserQueryBuilder($accountUser)
            ->getQuery()
            ->getResult();
        $this->assertCount(count($expectedAccountUserRoles) +  self::$defaultRolesCount, $actual);
        $roleIds = [];
        foreach ($actual as $role) {
            $roleIds[] = $role->getId();
        }
        foreach ($expectedAccountUserRoles as $roleReference) {
            $this->assertContains($this->getReference($roleReference)->getId(), $roleIds);
        }
    }

    /**
     * @return array
     */
    public function accountUserRolesDataProvider()
    {
        return [
            'user from account with custom role' => [
                'grzegorz.brzeczyszczykiewicz@example.com',
                [
                    LoadAccountUserRoleData::ROLE_WITH_ACCOUNT,
                    LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER,
                    LoadAccountUserRoleData::ROLE_WITH_WEBSITE,
                    LoadAccountUserRoleData::ROLE_WITHOUT_USER_AND_WEBSITE,
                    LoadAccountUserRoleData::ROLE_WITHOUT_ACCOUNT
                ]
            ],
            'user from account without custom roles' => [
                'orphan.user@test.com',
                [
                    LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER,
                    LoadAccountUserRoleData::ROLE_WITH_WEBSITE,
                    LoadAccountUserRoleData::ROLE_WITHOUT_USER_AND_WEBSITE,
                    LoadAccountUserRoleData::ROLE_WITHOUT_ACCOUNT
                ]
            ]
        ];
    }
}
