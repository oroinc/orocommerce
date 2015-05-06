<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class GroupControllerTest extends WebTestCase
{
    const GROUP_NAME = 'Test Group Name';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\UserAdminBundle\Tests\Functional\DataFixtures\LoadUserData'
            ]
        );
    }

    /**
     * Test create
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_user_admin_group_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_user_admin_group[name]'] = self::GROUP_NAME;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Role has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'frontend-group-grid',
            ['frontend-group-grid[_filter][name][value]' => self::GROUP_NAME]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_user_admin_group_update', ['id' => $id]));

        /** @var \Doctrine\Common\Persistence\ObjectManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManager();

        /** @var \OroB2B\Bundle\UserAdminBundle\Entity\User $user */
        $user = $manager->getRepository('OroB2BUserAdminBundle:User')->findOneBy([]);

        $this->assertNotNull($user);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_user_admin_group[name]']        = self::GROUP_NAME . ' Updated';
        $form['orob2b_user_admin_group[appendUsers]'] = $user->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Role has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_user_admin_group_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::GROUP_NAME, $result->getContent());
    }
}
