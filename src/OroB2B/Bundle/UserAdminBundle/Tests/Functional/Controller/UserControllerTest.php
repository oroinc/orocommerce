<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\UserAdminBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class UserControllerTest extends WebTestCase
{
    const FIRST_NAME = 'Grzegorz';
    const LAST_NAME = 'Brzeczyszczykiewicz';
    const EMAIL = 'grzegorz.brzeczyszczykiewicz@example.com';
    const PASSWORD = 'test';

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
        $form = $this->getUserCreationForm();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('User has been saved', $crawler->html());
    }

    /**
     * @return \Symfony\Component\DomCrawler\Form
     */
    protected function getUserCreationForm()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_user_admin_user_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_user_admin_user[firstName]']             = self::FIRST_NAME;
        $form['orob2b_user_admin_user[lastName]']              = self::LAST_NAME;
        $form['orob2b_user_admin_user[email]']                 = self::EMAIL;
        $form['orob2b_user_admin_user[plainPassword][first]']  = self::PASSWORD;
        $form['orob2b_user_admin_user[plainPassword][second]'] = self::PASSWORD;
        $form['orob2b_user_admin_user[enabled]']               = true;

        return $form;
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
        $form['orob2b_user_admin_user[firstName]'] = '_' . self::FIRST_NAME;
        $form['orob2b_user_admin_user[lastName]']  = '_' . self::LAST_NAME;
        $form['orob2b_user_admin_user[email]']     = 'changed.' . self::EMAIL;

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
