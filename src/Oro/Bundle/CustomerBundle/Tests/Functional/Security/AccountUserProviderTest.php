<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Security;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\CustomerBundle\Security\AccountUserProvider;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountUserProviderTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
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
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_profile'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertRoleHasPermission(
            LoadCustomerUserRoles::ADMINISTRATOR,
            [true, true, true, true, true, true, true]
        );
        $this->assertRoleHasPermission(LoadCustomerUserRoles::BUYER, [true, false, true, false, false, false, true]);

        $roleName = 'DENIED';
        $role = new CustomerUserRole(CustomerUserRole::PREFIX_ROLE . $roleName);
        $role->setLabel($roleName);
        $className = $this->getContainer()->getParameter('oro_customer.entity.customer_user_role.class');
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($className);
        $em->persist($role);
        $em->flush();

        $this->assertRoleHasPermission($roleName, [false, false, false, false, false, false, false]);
    }

    /**
     * @param string $roleName
     * @param array $expected
     */
    protected function assertRoleHasPermission($roleName, array $expected)
    {
        $className = $this->getContainer()->getParameter('oro_customer.entity.customer_user_role.class');
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($className);
        $repository = $em->getRepository($className);

        $role = $repository->findOneBy(['role' => CustomerUserRole::PREFIX_ROLE . $roleName]);
        $this->assertNotEmpty($role);

        /* @var $securityProvider AccountUserProvider */
        $securityProvider = $this->getContainer()->get('oro_customer.security.account_user_provider');

        /** @var AccountUser $user */
        $user = $securityProvider->getLoggedUser();
        $this->assertNotEmpty($user);

        $user->setRoles([$role]);
        $em->flush();

        $userClassName = $this->getContainer()->getParameter('oro_customer.entity.account_user.class');

        list(
            $isGrantedViewAccountUser,
            $isGrantedViewBasic,
            $isGrantedViewLocal,
            $isGrantedViewDeep,
            $isGrantedViewSystem,
            $isGrantedEditBasic,
            $isGrantedEditLocal
        ) = $expected;

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
        $this->assertEquals(
            $isGrantedViewDeep,
            $securityProvider->isGrantedViewDeep($userClassName),
            'isGrantedViewDeep ' . $roleName
        );
        $this->assertEquals(
            $isGrantedViewSystem,
            $securityProvider->isGrantedViewSystem($userClassName),
            'isGrantedViewSystem ' . $roleName
        );
        $this->assertEquals(
            $isGrantedEditBasic,
            $securityProvider->isGrantedEditBasic($userClassName),
            'isGrantedEditBasic ' . $roleName
        );
        $this->assertEquals(
            $isGrantedEditLocal,
            $securityProvider->isGrantedEditLocal($userClassName),
            'isGrantedEditLocal ' . $roleName
        );
    }
}
