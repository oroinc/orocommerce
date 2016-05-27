<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class AccountUserControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ]);
    }

    public function testQuoteGridOnAccountUserViewPage()
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.3');
        /** @var AccountUser $accountUser */
        $accountUser = $quote->getAccountUser();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_account_account_user_view',
                ['id' => $accountUser->getId()]
            )
        );
        $gridAttr = $crawler->filter('[id^=grid-account-user-view-quote-grid]')->first()->attr('data-page-component-options');
        $gridJsonElements = json_decode(html_entity_decode($gridAttr), true);

        $this->assertContains($quote->getOwner()->getFullName(), $gridAttr);
        $this->assertCount(
            count(LoadQuoteData::getQuotesFor('accountUser', $accountUser->getEmail())),
            $gridJsonElements['data']['data']
        );
    }
}
