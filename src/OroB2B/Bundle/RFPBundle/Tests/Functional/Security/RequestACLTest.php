<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Security;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadAccountUsersData;

/**
 * @dbIsolation
 */
class RequestACLTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadAccountUsersData'
        ]);
    }

    /**
     * @dataProvider permissionsDataProvider
     * @param int $level
     * @param array $permissions
     * @param int $expectedCode
     */
    public function testRFPPermissions($level, $permissions, $expectedCode)
    {
        $this->setRolePermissions($level);
        $this->login(LoadAccountUsersData::USER_EMAIL, LoadAccountUsersData::USER_PASSWORD);

        /** @var AccountUser $user */
        $user = $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $user);
        $this->assertEquals(LoadAccountUsersData::USER_EMAIL, $user->getUsername());

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_frontend_request_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Submit')->form();
        $form['orob2b_rfp_frontend_request_type[firstName]'] = LoadAccountUsersData::USER_NAME;
        $form['orob2b_rfp_frontend_request_type[lastName]']  = LoadAccountUsersData::USER_LAST_NAME;
        $form['orob2b_rfp_frontend_request_type[email]']     = LoadAccountUsersData::USER_EMAIL;
        $form['orob2b_rfp_frontend_request_type[phone]']     = 123456789;
        $form['orob2b_rfp_frontend_request_type[company]']   = 'Company name';
        $form['orob2b_rfp_frontend_request_type[role]']      = 'Manager';
        $form['orob2b_rfp_frontend_request_type[body]']      = 'This is test Request For Quote';

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, $expectedCode);

        // Check isset RFP request with first user ownership
        /** @var Request $request */
        $request = $this->getContainer()->get('doctrine')->getRepository('OroB2BRFPBundle:Request')
            ->findOneBy(['email' => LoadAccountUsersData::USER_EMAIL]);

        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\AccountUser', $request->getAccountUser());
        $this->assertEquals($user->getId(), $request->getAccountUser()->getId());

        // Check owner access
        $this->assertIsGranted($permissions['owner'], $request);

        // Login another user in same account
        $this->login(LoadAccountUsersData::SAME_ACCOUNT_USER_EMAIL, LoadAccountUsersData::SAME_ACCOUNT_USER_PASSWORD);
        $this->assertIsGranted($permissions['sameAccountUser'], $request);

        // Login another user in sub account
        $this->login(LoadAccountUsersData::SUB_ACCOUNT_USER_EMAIL, LoadAccountUsersData::SUB_ACCOUNT_USER_PASSWORD);
        $this->assertIsGranted($permissions['subAccountUser'], $request);

        // Login another user in another account
        $this->login(
            LoadAccountUsersData::NOT_SAME_ACCOUNT_USER_EMAIL,
            LoadAccountUsersData::NOT_SAME_ACCOUNT_USER_PASSWORD
        );
        $this->assertIsGranted($permissions['notSameAccountUser'], $request);
    }

    /**
     * @return array
     */
    public function permissionsDataProvider()
    {
        return [
            'none' => [
                'level' => AccessLevel::NONE_LEVEL,
                'permissions' => [
                    'owner' => false,
                    'sameAccountUser' => false,
                    'subAccountUser' => false,
                    'notSameAccountUser' => false,
                ],
                'expectedCode' => 200,
            ],
            'account user' => [
                'level' => AccessLevel::BASIC_LEVEL,
                'permissions' => [
                    'owner' => true,
                    'sameAccountUser' => false,
                    'subAccountUser' => false,
                    'notSameAccountUser' => false,
                ],
                'expectedCode' => 200,
            ],
            'account' => [
                'level' => AccessLevel::LOCAL_LEVEL,
                'permissions' => [
                    'owner' => true,
                    'sameAccountUser' => true,
                    'subAccountUser' => false,
                    'notSameAccountUser' => false,
                ],
                'expectedCode' => 200,
            ],
        ];
    }

    /**
     * @param int $level
     */
    protected function setRolePermissions($level)
    {
        $chainMetadataProvider = $this->getContainer()->get('oro_security.owner.metadata_provider.chain');
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        $role = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:AccountUserRole')
            ->getRepository('OroB2BAccountBundle:AccountUserRole')
            ->findOneBy(['role' => LoadAccountUsersData::BUYER]);

        $aclPrivilege = new AclPrivilege();
        $identity = new AclPrivilegeIdentity(
            'entity:OroB2B\Bundle\RFPBundle\Entity\Request',
            'orob2b.rfp.request.entity_label'
        );

        $aclPrivilege->setIdentity($identity);
        $permissions = [
            new AclPermission('VIEW', $level),
            new AclPermission('CREATE', $level),
            new AclPermission('EDIT', $level),
            new AclPermission('DELETE', $level),
            new AclPermission('ASSIGN', $level)
        ];

        foreach ($permissions as $permission) {
            $aclPrivilege->addPermission($permission);
        }

        $this->getContainer()->get('oro_security.acl.privilege_repository')->savePrivileges(
            $this->getContainer()->get('oro_security.acl.manager')->getSid($role),
            new ArrayCollection([$aclPrivilege])
        );

        $chainMetadataProvider->stopProviderEmulation();
    }

    /**
     * @param string $email
     * @param string $password
     */
    protected function login($email, $password)
    {
        // Logout previous user
        $this->client->request('GET', $this->getUrl('orob2b_account_account_user_security_logout'));

        // Login first user
        $this->initClient(
            [],
            $this->generateBasicAuthHeader($email, $password)
        );

        $this->client->request('GET', $this->getUrl('_frontend'));
    }

    /**
     * @param bool $expected
     * @param Request $request
     */
    protected function assertIsGranted($expected, Request $request)
    {
        $securityFacade = $this->getContainer()->get('oro_security.security_facade');

        $this->assertEquals($expected, $securityFacade->isGranted('VIEW', $request));
        $this->assertEquals($expected, $securityFacade->isGranted('CREATE', $request));
        $this->assertEquals($expected, $securityFacade->isGranted('EDIT', $request));
        $this->assertEquals($expected, $securityFacade->isGranted('DELETE', $request));
        $this->assertEquals($expected, $securityFacade->isGranted('ASSIGN', $request));
    }
}
