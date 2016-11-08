<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\Controller\AbstractUserControllerTest;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
     * @param string $email
     * @param string $password
     * @param bool $isPasswordGenerate
     * @param bool $isSendEmail
     * @param int $emailsCount
     */
    public function testCreate($email, $password, $isPasswordGenerate, $isSendEmail, $emailsCount)
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP
            )
        );
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_create'));
        $role = $this->getReference(LoadAccountUserACLData::ROLE_LOCAL);

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
        $form['oro_account_frontend_account_user[roles]'] = [$role->getId()];

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
     * @depends testCreate
     */
    public function testIndex()
    {
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
        $response = $this->client->requestGrid(
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
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertContains(self::UPDATED_EMAIL, $content);

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmail()
    {
        return self::EMAIL;
    }
}
