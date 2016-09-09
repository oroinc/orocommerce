<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class AccountControllerTest extends WebTestCase
{
    const ACCOUNT_NAME = 'Account_name';
    const UPDATED_NAME = 'Account_name_UP';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadInternalRating',
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadUserData'
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_account_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('account-accounts-grid', $crawler->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_account_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Account $parent */
        $parent = $this->getReference('account.level_1');
        /** @var AccountGroup $group */
        $group = $this->getReference('account_group.group1');
        /** @var AbstractEnumValue $internalRating */
        $internalRating = $this->getReference('internal_rating.1 of 5');
        $this->assertAccountSave($crawler, self::ACCOUNT_NAME, $parent, $group, $internalRating);

        /** @var Account $account */
        $account = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:Account')
            ->getRepository('OroAccountBundle:Account')
            ->findOneBy(['name' => self::ACCOUNT_NAME]);
        $this->assertNotEmpty($account);

        return $account->getId();
    }

    /**
     * @param int $id
     * @return int
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_account_update', ['id' => $id])
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
            $this->getUrl('oro_account_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains(self::UPDATED_NAME . ' - Accounts - Customers', $html);
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
                'oro_account_type[name]' => $name,
                'oro_account_type[parent]' => $parent->getId(),
                'oro_account_type[group]' => $group->getId(),
                'oro_account_type[internal_rating]' => $internalRating->getId(),
                'oro_account_type[salesRepresentatives]' => implode(',', [
                    $this->getReference(LoadUserData::USER1)->getId(),
                    $this->getReference(LoadUserData::USER2)->getId()
                ])
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Account has been saved', $html);
        $this->assertViewPage($html, $name, $parent, $group, $internalRating);
        $this->assertContains($this->getReference(LoadUserData::USER1)->getFullName(), $result->getContent());
        $this->assertContains($this->getReference(LoadUserData::USER2)->getFullName(), $result->getContent());
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
