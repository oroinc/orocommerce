<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\UserAdminBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class UserControllerTest extends WebTestCase
{
    const UPDATED_FIRST_NAME = 'UFirstName';
    const UPDATED_LAST_NAME = 'UpdLastName';
    const UPDATED_EMAIL = 'updated@example.com';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate($email, $password, $isPasswordGenerate, $isSendEmail, $emailsCount)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_user_admin_user_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_user_admin_user[firstName]']             = LoadUserData::FIRST_NAME;
        $form['orob2b_user_admin_user[lastName]']              = LoadUserData::LAST_NAME;
        $form['orob2b_user_admin_user[email]']                 = $email;
        $form['orob2b_user_admin_user[plainPassword][first]']  = $password;
        $form['orob2b_user_admin_user[plainPassword][second]'] = $password;
        $form['orob2b_user_admin_user[enabled]']               = true;
        $form['orob2b_user_admin_user[passwordGenerate]']      = $isPasswordGenerate;
        $form['orob2b_user_admin_user[sendEmail]']             = $isSendEmail;

        $this->client->submit($form);

        $collectedMessages = $this->client->getProfile()->getCollector('swiftmailer')->getMessages();

        $this->assertCount($emailsCount, $collectedMessages);

        if ($isSendEmail) {
            $this->assertMessage($email, array_shift($collectedMessages));
        }

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Frontend User has been saved', $crawler->html());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'simple create' => [
                'email' => LoadUserData::EMAIL,
                'password' => LoadUserData::PASSWORD,
                'isPasswordGenerate' => false,
                'isSendEmail' => false,
                'emailsCount' => 0,
            ],
            'create with email and without password generator' => [
                'email' => 'second@example.com',
                'password' => LoadUserData::PASSWORD,
                'isPasswordGenerate' => false,
                'isSendEmail' => true,
                'emailsCount' => 1,
            ],
            'create with email and password generator' => [
                'email' => 'third@example.com',
                'password' => '',
                'isPasswordGenerate' => true,
                'isSendEmail' => true,
                'emailsCount' => 1,
            ]
        ];
    }

    /**
     * @param string $email
     * @param \Swift_Message $message
     */
    protected function assertMessage($email, \Swift_Message $message)
    {
        /** @var \OroB2B\Bundle\UserAdminBundle\Entity\User $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $email]);

        $this->assertNotNull($user);

        $this->assertInstanceOf('\Swift_Message', $message);

        $this->assertEquals($email, key($message->getTo()));
        $this->assertEquals(
            $this->getContainer()->get('oro_config.manager')->get('oro_notification.email_notification_sender_email'),
            key($message->getFrom())
        );

        $this->assertContains($email, $message->getSubject());
        $this->assertContains($email, $message->getBody());
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BUserAdminBundle:User');
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_user_admin_user_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(LoadUserData::FIRST_NAME, $result->getContent());
        $this->assertContains(LoadUserData::LAST_NAME, $result->getContent());
        $this->assertContains(LoadUserData::EMAIL, $result->getContent());
    }

    /**
     * @depend testCreate
     * @return integer
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'user-admin-user-grid',
            [
                'user-admin-user-grid[_filter][firstName][value]' => LoadUserData::FIRST_NAME,
                'user-admin-user-grid[_filter][LastName][value]' => LoadUserData::LAST_NAME,
                'user-admin-user-grid[_filter][email][value]' => LoadUserData::EMAIL,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_user_admin_user_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_user_admin_user[firstName]'] = self::UPDATED_FIRST_NAME;
        $form['orob2b_user_admin_user[lastName]']  = self::UPDATED_LAST_NAME;
        $form['orob2b_user_admin_user[email]']     = self::UPDATED_EMAIL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Frontend User has been saved', $crawler->html());

        return $id;
    }

    /**
     * @depends testUpdate
     * @param integer $id
     * @return integer
     */
    public function testView($id)
    {
        $this->client->request('GET', $this->getUrl('orob2b_user_admin_user_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(
            sprintf('%s - Frontend Users - Frontend User Management - System', self::UPDATED_EMAIL),
            $result->getContent()
        );

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
            $this->getUrl('orob2b_user_admin_user_info', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::UPDATED_FIRST_NAME, $result->getContent());
        $this->assertContains(self::UPDATED_LAST_NAME, $result->getContent());
        $this->assertContains(self::UPDATED_EMAIL, $result->getContent());
    }
}
