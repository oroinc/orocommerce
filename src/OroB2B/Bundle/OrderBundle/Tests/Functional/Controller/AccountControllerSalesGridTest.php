<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class AccountControllerSalesGridTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], array_merge(static::generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
            ]
        );

    }

    public function testAccountUserQuoteViewGrid()
    {
        /** @var $accountUser AccountUser */
        $accountUser = $this->getReference(LoadUserData::ACCOUNT2_USER1);
//        $aa=$accountUser->getAccount();
        $quote = $this->getReference(LoadQuoteData::QUOTE7);
        /** @var $quote Quote */
//        $q=$quote->getAccountUser();
//        $a=$quote->getAccount();
        $quote->setAccountUser($accountUser);
        $this->client->getContainer()->get('doctrine')->getManager()->persist($quote);
        $this->client->getContainer()->get('doctrine')->getManager()->flush();
        $response = $this->client->requestGrid(
            [
                'gridName' => 'account-user-orders-grid',
                'account-user-orders-grid[account_user_id]' => $accountUser->getId()
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $content = $response->getContent();
        $this->assertContains($content, LoadQuoteData::QUOTE3);
    }

    public function testAccountQuoteViewGrid()
    {
        /** @var $account Account */
        $account = $this->getReference(LoadUserData::ACCOUNT1);
        /** @var $quote Quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
    }
}
