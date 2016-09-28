<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

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
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
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
                'oro_account_account_user_view',
                ['id' => $accountUser->getId()]
            )
        );
        $gridAttr = $crawler->filter('[id^=grid-account-user-view-quote-grid]')
            ->first()->attr('data-page-component-options');
        $gridJsonElements = json_decode(html_entity_decode($gridAttr), true);

        $this->assertContains($quote->getOwner()->getFullName(), $gridAttr);
        $this->assertCount(
            count(LoadQuoteData::getQuotesFor('accountUser', $accountUser->getEmail())),
            $gridJsonElements['data']['data']
        );
    }
}
