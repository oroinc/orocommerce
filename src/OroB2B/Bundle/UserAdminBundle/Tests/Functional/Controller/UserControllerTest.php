<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\UserAdminBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class UserControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * Test create
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_user_admin_user_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_user_admin_user[firstName]']             = LoadUserData::FIRST_NAME;
        $form['orob2b_user_admin_user[lastName]']              = LoadUserData::LAST_NAME;
        $form['orob2b_user_admin_user[email]']                 = LoadUserData::EMAIL;
        $form['orob2b_user_admin_user[plainPassword][first]']  = LoadUserData::PASSWORD;
        $form['orob2b_user_admin_user[plainPassword][second]'] = LoadUserData::PASSWORD;
        $form['orob2b_user_admin_user[enabled]']               = true;
        $form['orob2b_user_admin_user[passwordGenerate]']      = true;
        $form['orob2b_user_admin_user[sendEmail]']             = true;

        $this->client->submit($form);

        $collectedMessages = $this->client->getProfile()->getCollector('swiftmailer')->getMessages();

        $this->assertCount(1, $collectedMessages);
        $this->assertMessage(array_shift($collectedMessages));

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('User has been saved', $crawler->html());
    }

    /**
     * @param \Swift_Message $message
     */
    protected function assertMessage(\Swift_Message $message)
    {
        /** @var \OroB2B\Bundle\UserAdminBundle\Entity\User $user */
        $user = $this->getUserRepository()->findOneBy(['email' => LoadUserData::EMAIL]);

        $this->assertNotNull($user);

        $this->assertInstanceOf('\Swift_Message', $message);

        $this->assertEquals(LoadUserData::EMAIL, key($message->getTo()));
        $this->assertEquals(
            $this->getContainer()->get('oro_config.manager')->get('oro_notification.email_notification_sender_email'),
            key($message->getFrom())
        );

        $this->assertContains(LoadUserData::EMAIL, $message->getSubject());
        $this->assertContains(LoadUserData::EMAIL, $message->getBody());
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
        $form['orob2b_user_admin_user[firstName]'] = '_' . LoadUserData::FIRST_NAME;
        $form['orob2b_user_admin_user[lastName]']  = '_' . LoadUserData::LAST_NAME;
        $form['orob2b_user_admin_user[email]']     = 'changed.' . LoadUserData::EMAIL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('User has been saved', $crawler->html());

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
            sprintf('%s - Users - Frontend User Management - System', 'changed.' . LoadUserData::EMAIL),
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
        $this->assertContains('_' . LoadUserData::FIRST_NAME, $result->getContent());
        $this->assertContains('_' . LoadUserData::LAST_NAME, $result->getContent());
        $this->assertContains('changed.' . LoadUserData::EMAIL, $result->getContent());
    }
}
