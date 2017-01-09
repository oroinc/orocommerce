<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\Controller\AbstractUserControllerTest;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

/**
 * @dbIsolation
 */
class AccountUserControllerTest extends AbstractUserControllerTest
{
    const NAME_PREFIX = 'NamePrefix';
    const MIDDLE_NAME = 'MiddleName';
    const NAME_SUFFIX = 'NameSuffix';
    const EMAIL = 'first@example.com';
    const FIRST_NAME = 'John';
    const LAST_NAME = 'Doe';

    const UPDATED_NAME_PREFIX = 'UNamePrefix';
    const UPDATED_FIRST_NAME = 'UFirstName';
    const UPDATED_MIDDLE_NAME = 'UMiddleName';
    const UPDATED_LAST_NAME = 'UpdLastName';
    const UPDATED_NAME_SUFFIX = 'UNameSuffix';
    const UPDATED_EMAIL = 'updated@example.com';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadAccountUserACLData::class,
            ]
        );
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $email
     * @param string $password
     * @param bool $isPasswordGenerate
     * @param bool $isSendEmail
     * @param int $emailsCount
     */
    public function testCreate($email, $password, $isPasswordGenerate, $isSendEmail, $emailsCount)
    {
        $this->loginUser(LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_DEEP);

        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_create'));
        $form = $crawler->selectButton('Save')->form();
        $form['oro_account_frontend_account_user[enabled]'] = true;
        $form['oro_account_frontend_account_user[namePrefix]'] = self::NAME_PREFIX;
        $form['oro_account_frontend_account_user[firstName]'] = self::FIRST_NAME;
        $form['oro_account_frontend_account_user[middleName]'] = self::MIDDLE_NAME;
        $form['oro_account_frontend_account_user[lastName]'] = self::LAST_NAME;
        $form['oro_account_frontend_account_user[nameSuffix]'] = self::NAME_SUFFIX;
        $form['oro_account_frontend_account_user[email]'] = $email;
        $form['oro_account_frontend_account_user[birthday]'] = date('Y-m-d');
        $form['oro_account_frontend_account_user[plainPassword][first]'] = $password;
        $form['oro_account_frontend_account_user[plainPassword][second]'] = $password;
        $form['oro_account_frontend_account_user[passwordGenerate]'] = $isPasswordGenerate;
        $form['oro_account_frontend_account_user[sendEmail]'] = $isSendEmail;

        /** @var ChoiceFormField[] $roleChoices */
        $roleChoices = $form['oro_account_frontend_account_user[roles]'];
        $this->assertCount(6, $roleChoices);
        $roleChoices[0]->tick();
        $this->client->submit($form);

        /** @var \Swift_Plugins_MessageLogger $emailLogging */
        $emailLogger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');
        $emailMessages = $emailLogger->getMessages();

        $this->assertCount($emailsCount, $emailMessages);

        if ($isSendEmail) {
            $this->assertMessage($email, array_shift($emailMessages));
        }

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Customer User has been saved', $crawler->html());
    }

    /**
     * @dataProvider testCreatePermissionDeniedDataProvider
     * @group frontend-ACL
     * @param string $login
     * @param int $status
     */
    public function testCreatePermissionDenied($login, $status)
    {
        $this->loginUser($login);
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_create'));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, $status);
    }

    /**
     * @return array
     */
    public function testCreatePermissionDeniedDataProvider()
    {
        return [
            'anonymous user' => [
                'login' => '',
                'status' => 401,
            ],
            'user without create permissions' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 403,
            ],
        ];
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $this->loginUser(LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_DEEP);
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::FIRST_NAME, $result->getContent());
        $this->assertContains(self::LAST_NAME, $result->getContent());
        $this->assertContains(self::EMAIL, $result->getContent());
    }

    /**
     * @depend testCreate
     * @return integer
     */
    public function testUpdate()
    {
        $this->loginUser(LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_DEEP);
        $response = $this->client->requestFrontendGrid(
            'frontend-account-account-user-grid',
            [
                'frontend-account-account-user-grid[_filter][firstName][value]' => self::FIRST_NAME,
                'frontend-account-account-user-grid[_filter][LastName][value]' => self::LAST_NAME,
                'frontend-account-account-user-grid[_filter][email][value]' => self::EMAIL,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_account_user_update', ['id' => $id])
        );

        $form = $crawler->selectButton('Save')->form();
        $form['oro_account_frontend_account_user[enabled]'] = false;
        $form['oro_account_frontend_account_user[namePrefix]'] = self::UPDATED_NAME_PREFIX;
        $form['oro_account_frontend_account_user[firstName]'] = self::UPDATED_FIRST_NAME;
        $form['oro_account_frontend_account_user[middleName]'] = self::UPDATED_MIDDLE_NAME;
        $form['oro_account_frontend_account_user[lastName]'] = self::UPDATED_LAST_NAME;
        $form['oro_account_frontend_account_user[nameSuffix]'] = self::UPDATED_NAME_SUFFIX;
        $form['oro_account_frontend_account_user[email]'] = self::UPDATED_EMAIL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Customer User has been saved', $crawler->html());

        return $id;
    }

    /**
     * @depends testUpdate
     * @param integer $id
     * @return integer
     */
    public function testView($id)
    {
        $this->loginUser(LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_DEEP);
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertContains(self::UPDATED_EMAIL, $content);

        return $id;
    }

    /**
     * @group frontend-ACL
     * @dataProvider ACLProvider
     *
     * @param string $route
     * @param string $resource
     * @param string $user
     * @param int $status
     */
    public function testACL($route, $resource, $user, $status)
    {
        $this->loginUser($user);
        /* @var $resource CustomerUser */
        $resource = $this->getReference($resource);

        $this->client->request(
            'GET',
            $this->getUrl(
                $route,
                ['id' => $resource->getId()]
            )
        );

        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, $status);
    }

    /**
     * @return array
     */
    public function ACLProvider()
    {
        return [
            'VIEW (anonymous user)' => [
                'route' => 'oro_customer_frontend_account_user_view',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => '',
                'status' => 401,
            ],
            'VIEW (user from another account)' => [
                'route' => 'oro_customer_frontend_account_user_view',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'VIEW (user from parent account : DEEP_VIEW_ONLY)' => [
                'route' => 'oro_customer_frontend_account_user_view',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 200,
            ],
            'VIEW (user from parent account : LOCAL)' => [
                'route' => 'oro_customer_frontend_account_user_view',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'VIEW (user from same account : LOCAL)' => [
                'route' => 'oro_customer_frontend_account_user_view',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_DEEP,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 200,
            ],
            'UPDATE (anonymous user)' => [
                'route' => 'oro_customer_frontend_account_user_update',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => '',
                'status' => 401,
            ],
            'UPDATE (user from another account)' => [
                'route' => 'oro_customer_frontend_account_user_update',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'UPDATE (user from parent account : DEEP)' => [
                'route' => 'oro_customer_frontend_account_user_update',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 200,
            ],
            'UPDATE (user from parent account : LOCAL_VIEW_ONLY)' => [
                'route' => 'oro_customer_frontend_account_user_update',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 403,
            ],
            'UPDATE (user from same account : LOCAL_VIEW_ONLY)' => [
                'route' => 'oro_customer_frontend_account_user_update',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'status' => 403,
            ],
            'UPDATE (user from same account : LOCAL)' => [
                'route' => 'oro_customer_frontend_account_user_update',
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 200,
            ],
        ];
    }

    /**
     * @group frontend-ACL
     * @dataProvider gridACLProvider
     *
     * @param string $user
     * @param string $indexResponseStatus
     * @param string $gridResponseStatus
     * @param array $data
     */
    public function testGridACL($user, $indexResponseStatus, $gridResponseStatus, array $data = [])
    {
        $this->loginUser($user);
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_index'));
        $this->assertSame($indexResponseStatus, $this->client->getResponse()->getStatusCode());
        $response = $this->client->requestGrid(
            [
                'gridName' => 'frontend-account-account-user-grid',
            ]
        );

        self::assertResponseStatusCodeEquals($response, $gridResponseStatus);
        if (200 === $gridResponseStatus) {
            $result = self::jsonToArray($response->getContent());
            $actual = array_column($result['data'], 'id');
            $actual = array_map('intval', $actual);
            $expected = array_map(
                function ($ref) {
                    return $this->getReference($ref)->getId();
                },
                $data
            );
            sort($expected);
            sort($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function gridACLProvider()
    {
        return [
            'NOT AUTHORISED' => [
                'user' => '',
                'indexResponseStatus' => 401,
                'gridResponseStatus' => 403,
                'data' => [],
            ],
            'DEEP: all siblings and children' => [
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                    LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                    LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                    LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                    LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_DEEP,
                    LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                    LoadAccountUserACLData::USER_ACCOUNT_1_2_ROLE_DEEP,
                    LoadAccountUserACLData::USER_ACCOUNT_1_2_ROLE_LOCAL,
                ],
            ],
            'LOCAL: all siblings' => [
                'user' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                    LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                    LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                    LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmail()
    {
        return self::EMAIL;
    }
}
