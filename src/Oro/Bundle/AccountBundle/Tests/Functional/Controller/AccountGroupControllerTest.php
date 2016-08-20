<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;

/**
 * @dbIsolation
 */
class AccountGroupControllerTest extends WebTestCase
{
    const NAME = 'Group_name';
    const UPDATED_NAME = 'Group_name_UP';
    const ADD_NOTE_BUTTON = 'Add note';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->entityManager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroAccountBundle:AccountGroup');

        $this->loadFixtures(
            [
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups'
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_group_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('account-groups-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_group_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertAccountGroupSave(
            $crawler,
            self::NAME,
            [
                $this->getReference('account.level_1.1'),
                $this->getReference('account.level_1.2')
            ]
        );
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $id = $this->getGroupId(self::NAME);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_group_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertAccountGroupSave(
            $crawler,
            self::UPDATED_NAME,
            [
                $this->getReference('account.level_1.1.1')
            ],
            [
                $this->getReference('account.level_1.2')
            ]
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_group_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains(self::UPDATED_NAME . ' - Account Groups - Customers', $html);
        $this->assertContains(self::ADD_NOTE_BUTTON, $html);
        $this->assertViewPage($html, self::UPDATED_NAME);
    }

    /**
     * @param Crawler $crawler
     * @param string $name
     * @param Account[] $appendAccounts
     * @param Account[] $removeAccounts
     */
    protected function assertAccountGroupSave(
        Crawler $crawler,
        $name,
        array $appendAccounts = [],
        array $removeAccounts = []
    ) {
        $appendAccountIds = array_map(
            function (Account $account) {
                return $account->getId();
            },
            $appendAccounts
        );
        $removeAccountIds = array_map(
            function (Account $account) {
                return $account->getId();
            },
            $removeAccounts
        );
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_account_group_type[name]' => $name,
                'orob2b_account_group_type[appendAccounts]' => implode(',', $appendAccountIds),
                'orob2b_account_group_type[removeAccounts]' => implode(',', $removeAccountIds)
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Account group has been saved', $html);
        $this->assertViewPage($html, $name);

        foreach ($appendAccounts as $account) {
            $this->assertContains($account->getName(), $html);
        }
        foreach ($removeAccounts as $account) {
            $this->assertNotContains($account->getName(), $html);
        }
    }

    /**
     * @param string $html
     * @param string $name
     */
    protected function assertViewPage($html, $name)
    {
        $this->assertContains($name, $html);
    }

    /**
     * @param string $name
     * @return int
     */
    protected function getGroupId($name)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:AccountGroup')
            ->getRepository('OroAccountBundle:AccountGroup')
            ->findOneBy(['name' => $name]);

        return $accountGroup->getId();
    }
}
