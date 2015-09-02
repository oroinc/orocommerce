<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\AbstractUserControllerTest;
use Symfony\Bridge\Swiftmailer\DataCollector\MessageDataCollector;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

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
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
        );
        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
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
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_create'));

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUserRole $role */
        $role = $this->getUserRoleRepository()->findOneBy([]);

        $this->assertNotNull($role);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_account_frontend_account_user[enabled]'] = true;
        $form['orob2b_account_frontend_account_user[namePrefix]'] = self::NAME_PREFIX;
        $form['orob2b_account_frontend_account_user[firstName]'] = self::FIRST_NAME;
        $form['orob2b_account_frontend_account_user[middleName]'] = self::MIDDLE_NAME;
        $form['orob2b_account_frontend_account_user[lastName]'] = self::LAST_NAME;
        $form['orob2b_account_frontend_account_user[nameSuffix]'] = self::NAME_SUFFIX;
        $form['orob2b_account_frontend_account_user[email]'] = $email;
        $form['orob2b_account_frontend_account_user[birthday]'] = date('Y-m-d');
        $form['orob2b_account_frontend_account_user[plainPassword][first]'] = $password;
        $form['orob2b_account_frontend_account_user[plainPassword][second]'] = $password;
        $form['orob2b_account_frontend_account_user[passwordGenerate]'] = $isPasswordGenerate;
        $form['orob2b_account_frontend_account_user[sendEmail]'] = $isSendEmail;
        $form['orob2b_account_frontend_account_user[roles]'] = [$role->getId()];

        $this->client->submit($form);
        /** @var MessageDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('swiftmailer');
        $collectedMessages = $collector->getMessages();

        $this->assertCount($emailsCount, $collectedMessages);

        if ($isSendEmail) {
            $this->assertMessage($email, array_shift($collectedMessages));
        }

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Account User has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_index'));
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
        $response = $this->requestFrontendGrid(
            'frontend-account-account-user-grid',
            [
                'frontend-account-account-user-grid[_filter][firstName][value]' => self::FIRST_NAME,
                'frontend-account-account-user-grid[_filter][LastName][value]' => self::LAST_NAME,
                'frontend-account-account-user-grid[_filter][email][value]' => self::EMAIL
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_update', ['id' => $id])
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_account_frontend_account_user[enabled]'] = false;
        $form['orob2b_account_frontend_account_user[namePrefix]'] = self::UPDATED_NAME_PREFIX;
        $form['orob2b_account_frontend_account_user[firstName]'] = self::UPDATED_FIRST_NAME;
        $form['orob2b_account_frontend_account_user[middleName]'] = self::UPDATED_MIDDLE_NAME;
        $form['orob2b_account_frontend_account_user[lastName]'] = self::UPDATED_LAST_NAME;
        $form['orob2b_account_frontend_account_user[nameSuffix]'] = self::UPDATED_NAME_SUFFIX;
        $form['orob2b_account_frontend_account_user[email]'] = self::UPDATED_EMAIL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Account User has been saved', $crawler->html());

        return $id;
    }

    /**
     * @depends testUpdate
     * @param integer $id
     * @return integer
     */
    public function testView($id)
    {
        $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertContains(self::UPDATED_EMAIL, $content);

        return $id;
    }

    /**
     * @depends testUpdate
     * @param integer $id
     */
    public function testInfo($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_info', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->find($id);
        $this->assertNotNull($user);

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUserRole $role */
        $roles = $user->getRoles();
        $role = reset($roles);
        $this->assertNotNull($role);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::UPDATED_FIRST_NAME, $result->getContent());
        $this->assertContains(self::UPDATED_LAST_NAME, $result->getContent());
        $this->assertContains(self::UPDATED_EMAIL, $result->getContent());
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmail()
    {
        return self::EMAIL;
    }
}
