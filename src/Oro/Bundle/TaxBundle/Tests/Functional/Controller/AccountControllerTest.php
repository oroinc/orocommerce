<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;

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
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadInternalRating',
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes'
            ]
        );
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
        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $this->assertAccountSave($crawler, self::ACCOUNT_NAME, $parent, $group, $internalRating, $accountTaxCode);

        /** @var Account $taxAccount */
        $taxAccount = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:Account')
            ->getRepository('OroAccountBundle:Account')
            ->findOneBy(['name' => self::ACCOUNT_NAME]);
        $this->assertNotEmpty($taxAccount);

        return $taxAccount->getId();
    }

    /**
     * @param $id int
     * @depends testCreate
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

        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $this->assertContains($accountTaxCode->getCode(), $html);
    }

    /**
     * @depends testView
     */
    public function testTaxCodeViewContainsEntity()
    {
        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_tax_account_tax_code_view', ['id' => $accountTaxCode->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $grid = $crawler->filter('.inner-grid')->eq(0)->attr('data-page-component-options');
        $this->assertContains(self::ACCOUNT_NAME, $grid);
    }

    /**
     * @depends testTaxCodeViewContainsEntity
     */
    public function testGrid()
    {
        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $response = $this->client->requestGrid(
            'account-accounts-grid',
            ['account-accounts-grid[_filter][name][value]' => self::ACCOUNT_NAME]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertArrayHasKey('taxCode', $result);
        $this->assertArrayHasKey('accountGroupTaxCode', $result);
        $this->assertEquals($accountTaxCode->getCode(), $result['taxCode']);
        $this->assertNull($result['accountGroupTaxCode']);
    }

    /**
     * @depends testGrid
     */
    public function testGridAccountTaxCodeFallbackToAccountGroup()
    {
        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_2);

        /** @var Account $account */
        $account = $this->getReference('account.level_1.2');

        $response = $this->client->requestGrid(
            'account-accounts-grid',
            ['account-accounts-grid[_filter][name][value]' => $account->getName()]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertArrayHasKey('taxCode', $result);
        $this->assertEmpty($result['taxCode']);

        $this->assertArrayHasKey('accountGroupTaxCode', $result);
        $this->assertEquals($accountTaxCode->getCode(), $result['accountGroupTaxCode']);
    }

    /**
     * @depends testGridAccountTaxCodeFallbackToAccountGroup
     */
    public function testViewAccountTaxCodeFallbackToAccountGroup()
    {
        /** @var Account $account */
        $account = $this->getReference('account.level_1.2');

        $response = $this->client->requestGrid(
            'account-accounts-grid',
            ['account-accounts-grid[_filter][name][value]' => $account->getName()]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();

        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_2);

        $this->assertContains($accountTaxCode->getCode(), $html);
        $this->assertContains('(Defined for Account Group)', $html);
    }

    /**
     * @param Crawler           $crawler
     * @param string            $name
     * @param Account           $parent
     * @param AccountGroup      $group
     * @param AbstractEnumValue $internalRating
     * @param AccountTaxCode    $accountTaxCode
     */
    protected function assertAccountSave(
        Crawler $crawler,
        $name,
        Account $parent,
        AccountGroup $group,
        AbstractEnumValue $internalRating,
        AccountTaxCode $accountTaxCode
    ) {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_account_type[name]' => $name,
                'orob2b_account_type[parent]' => $parent->getId(),
                'orob2b_account_type[group]' => $group->getId(),
                'orob2b_account_type[internal_rating]' => $internalRating->getId(),
                'orob2b_account_type[taxCode]' => $accountTaxCode->getId(),
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Account has been saved', $html);
        $this->assertViewPage($html, $name, $parent, $group, $internalRating, $accountTaxCode);
    }

    /**
     * @param string            $html
     * @param string            $name
     * @param Account           $parent
     * @param AccountGroup      $group
     * @param AbstractEnumValue $internalRating
     * @param AccountTaxCode    $accountTaxCode
     */
    protected function assertViewPage(
        $html,
        $name,
        Account $parent,
        AccountGroup $group,
        AbstractEnumValue $internalRating,
        AccountTaxCode $accountTaxCode
    ) {
        $groupName = $group->getName();
        $this->assertContains($name, $html);
        $this->assertContains($parent->getName(), $html);
        $this->assertContains($groupName, $html);
        $this->assertContains($internalRating->getName(), $html);
        $this->assertContains($accountTaxCode->getCode(), $html);
    }
}
