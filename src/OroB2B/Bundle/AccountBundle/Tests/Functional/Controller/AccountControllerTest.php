<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

/**
 * @dbIsolation
 */
class AccountControllerTest extends WebTestCase
{
    const ACCOUNT_NAME = 'Account_name';
    const UPDATED_NAME = 'Account_name_UP';

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadInternalRating'
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_account_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Account $parent */
        $parent = $this->getReference('account.level_1');
        /** @var AccountGroup $group */
        $group = $this->getReference('account_group.group1');
        /** @var AbstractEnumValue $internalRating */
        $internalRating = $this->getReference('internal_rating.1 of 5');
        $this->assertAccountSave($crawler, self::ACCOUNT_NAME, $parent, $group, $internalRating);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'account-accounts-grid',
            ['account-accounts-grid[_filter][name][value]' => self::ACCOUNT_NAME]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_update', ['id' => $result['id']])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Account $newParent */
        $newParent = $this->getReference('account.level_1.1');
        /** @var AccountGroup $newGroup */
        $newGroup = $this->getReference('account_group.group2');
        /** @var AbstractEnumValue $internalRating */
        $internalRating = $this->getReference('internal_rating.2 of 5');
        $this->assertAccountSave($crawler, self::UPDATED_NAME, $newParent, $newGroup, $internalRating);

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
            $this->getUrl('orob2b_account_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains(self::UPDATED_NAME . ' - Accounts - Accounts', $html);
        $this->assertContains('Add attachment', $html);
        $this->assertContains('Add note', $html);
        $this->assertContains('Address Book', $html);
        /** @var Account $newParent */
        $newParent = $this->getReference('account.level_1.1');
        /** @var AccountGroup $newGroup */
        $newGroup = $this->getReference('account_group.group2');
        /** @var AbstractEnumValue $internalRating */
        $internalRating = $this->getReference('internal_rating.2 of 5');
        $this->assertViewPage($html, self::UPDATED_NAME, $newParent, $newGroup, $internalRating);
    }

    /**
     * @param Crawler           $crawler
     * @param string            $name
     * @param Account           $parent
     * @param AccountGroup      $group
     * @param AbstractEnumValue $internalRating
     */
    protected function assertAccountSave(
        Crawler $crawler,
        $name,
        Account $parent,
        AccountGroup $group,
        AbstractEnumValue $internalRating
    ) {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_account_type[name]' => $name,
                'orob2b_account_type[parent]' => $parent->getId(),
                'orob2b_account_type[group]' => $group->getId(),
                'orob2b_account_type[internal_rating]' => $internalRating->getId(),

            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Account has been saved', $html);
        $this->assertViewPage($html, $name, $parent, $group, $internalRating);
    }

    /**
     * @param string $html
     * @param string $name
     * @param Account $parent
     * @param AccountGroup $group
     * @param AbstractEnumValue $internalRating
     */
    protected function assertViewPage(
        $html,
        $name,
        Account $parent,
        AccountGroup $group,
        AbstractEnumValue $internalRating
    ) {
        $this->assertContains($name, $html);
        $this->assertContains($parent->getName(), $html);
        $this->assertContains($group->getName(), $html);
        $this->assertContains($internalRating->getName(), $html);
    }
}
