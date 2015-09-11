<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Security;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Migrations\Data\ORM\LoadAccountUserRoles;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountUserProviderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testAccountPermissions()
    {
        // init tokens
        $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_profile'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertRoleHasAccountViewPermission(LoadAccountUserRoles::ADMINISTRATOR, [true, true, true]);
        $this->assertRoleHasAccountViewPermission(LoadAccountUserRoles::BUYER, [true, false, true]);

        $roleName = 'DENIED';
        $role = new AccountUserRole(AccountUserRole::PREFIX_ROLE . $roleName);
        $role->setLabel($roleName);
        $className = $this->getContainer()->getParameter('orob2b_account.entity.account_user_role.class');
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($className);
        $em->persist($role);
        $em->flush();

        $this->assertRoleHasAccountViewPermission($roleName, [false, false, false]);
    }

    /**
     * @param string $roleName
     * @param array $expected
     */
    protected function assertRoleHasAccountViewPermission($roleName, array $expected)
    {
        $className = $this->getContainer()->getParameter('orob2b_account.entity.account_user_role.class');
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($className);
        $repository = $em->getRepository($className);

        $role = $repository->findOneBy(['role' => AccountUserRole::PREFIX_ROLE . $roleName]);
        $this->assertNotEmpty($role);

        $securityProvider = $this->getContainer()->get('orob2b_account.security.account_user_provider');

        /** @var AccountUser $user */
        $user = $securityProvider->getLoggedUser();
        $this->assertNotEmpty($user);

        $user->setRoles([$role]);
        $em->flush();

        $userClassName = $this->getContainer()->getParameter('orob2b_account.entity.account_user.class');

        list($isGrantedViewAccountUser, $isGrantedViewBasic, $isGrantedViewLocal) = $expected;

        $this->assertEquals(
            $isGrantedViewAccountUser,
            $securityProvider->isGrantedViewAccountUser($userClassName),
            'isGrantedViewAccountUser ' . $roleName
        );
        $this->assertEquals(
            $isGrantedViewBasic,
            $securityProvider->isGrantedViewBasic($userClassName),
            'isGrantedViewBasic ' . $roleName
        );
        $this->assertEquals(
            $isGrantedViewLocal,
            $securityProvider->isGrantedViewLocal($userClassName),
            'isGrantedViewLocal ' . $roleName
        );
    }
}
