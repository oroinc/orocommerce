<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Security;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadCustomerUsersData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class RequestACLTest extends WebTestCase
{
    use RolePermissionExtension;

    private WorkflowManager $manager;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([LoadCustomerUsersData::class]);

        $this->manager = $this->getContainer()->get('oro_workflow.manager');
    }

    /**
     * @dataProvider permissionsDataProvider
     */
    public function testRFPPermissions(int $level, array  $permissions, int $expectedCode)
    {
        $this->manager->deactivateWorkflow('b2b_rfq_frontoffice_default');

        $this->setRolePermissions($level);
        $this->login(LoadCustomerUsersData::USER_EMAIL, LoadCustomerUsersData::USER_PASSWORD);

        /** @var CustomerUser $user */
        $user = $this->getContainer()->get('oro_security.token_accessor')->getUser();
        $this->assertInstanceOf(CustomerUser::class, $user);
        $this->assertEquals(LoadCustomerUsersData::USER_EMAIL, $user->getUsername());

        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Submit Request')->form();
        $form->remove('oro_rfp_frontend_request[requestProducts][0]');
        $form['oro_rfp_frontend_request[firstName]'] = LoadCustomerUsersData::USER_NAME;
        $form['oro_rfp_frontend_request[lastName]'] = LoadCustomerUsersData::USER_LAST_NAME;
        $form['oro_rfp_frontend_request[email]'] = LoadCustomerUsersData::USER_EMAIL;
        $form['oro_rfp_frontend_request[phone]'] = 123456789;
        $form['oro_rfp_frontend_request[company]'] = 'Company name';
        $form['oro_rfp_frontend_request[role]'] = 'Manager';
        $form['oro_rfp_frontend_request[note]'] = 'This is test Request For Quote';

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, $expectedCode);

        // Check isset RFP request with first user ownership
        /** @var Request $request */
        $request = $this->getContainer()->get('doctrine')
            ->getRepository(Request::class)
            ->findOneBy(['email' => LoadCustomerUsersData::USER_EMAIL]);

        $this->assertInstanceOf(CustomerUser::class, $request->getCustomerUser());
        $this->assertEquals($user->getId(), $request->getCustomerUser()->getId());

        // Check owner access
        $this->assertIsGranted($permissions['owner'], $request);

        // Login another user in same customer
        $this->login(LoadCustomerUsersData::SAME_ACCOUNT_USER_EMAIL, LoadCustomerUsersData::SAME_ACCOUNT_USER_PASSWORD);
        $this->assertIsGranted($permissions['sameCustomerUser'], $request);

        // Login another user in sub customer
        $this->login(LoadCustomerUsersData::SUB_ACCOUNT_USER_EMAIL, LoadCustomerUsersData::SUB_ACCOUNT_USER_PASSWORD);
        $this->assertIsGranted($permissions['subCustomerUser'], $request);

        // Login another user in another customer
        $this->login(
            LoadCustomerUsersData::NOT_SAME_ACCOUNT_USER_EMAIL,
            LoadCustomerUsersData::NOT_SAME_ACCOUNT_USER_PASSWORD
        );
        $this->assertIsGranted($permissions['notSameCustomerUser'], $request);
    }

    public function permissionsDataProvider(): array
    {
        return [
            'customer user' => [
                'level' => AccessLevel::BASIC_LEVEL,
                'permissions' => [
                    'owner' => true,
                    'sameCustomerUser' => false,
                    'subCustomerUser' => false,
                    'notSameCustomerUser' => false,
                ],
                'expectedCode' => 200,
            ],
            'customer' => [
                'level' => AccessLevel::LOCAL_LEVEL,
                'permissions' => [
                    'owner' => true,
                    'sameCustomerUser' => true,
                    'subCustomerUser' => false,
                    'notSameCustomerUser' => false,
                ],
                'expectedCode' => 200,
            ],
        ];
    }

    private function setRolePermissions(int $level): void
    {
        $chainMetadataProvider = $this->getContainer()->get('oro_security.owner.metadata_provider.chain');
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        $this->updateRolePermissions(
            LoadCustomerUsersData::BUYER,
            Request::class,
            [
                'VIEW' => $level,
                'CREATE' => $level,
                'EDIT' => $level,
                'DELETE' => $level,
                'ASSIGN' => $level
            ]
        );

        $chainMetadataProvider->stopProviderEmulation();
    }

    private function login(string $email, string $password): void
    {
        $this->initClient([], $this->generateBasicAuthHeader($email, $password));
        $this->client->useHashNavigation(true);
        $this->client->request('GET', '/'); // any page to apply new user
    }

    private function assertIsGranted(bool $expected, Request $request): void
    {
        $authorizationChecker = $this->getContainer()->get('security.authorization_checker');

        $this->assertEquals($expected, $authorizationChecker->isGranted('VIEW', $request));
        $this->assertEquals($expected, $authorizationChecker->isGranted('CREATE', $request));
        $this->assertEquals($expected, $authorizationChecker->isGranted('EDIT', $request));
        $this->assertEquals($expected, $authorizationChecker->isGranted('DELETE', $request));
        $this->assertEquals($expected, $authorizationChecker->isGranted('ASSIGN', $request));
    }
}
