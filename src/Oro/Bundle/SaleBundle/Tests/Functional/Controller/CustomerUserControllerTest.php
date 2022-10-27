<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CustomerUserControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ]);
    }

    public function testQuoteGridOnCustomerUserViewPage()
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.3');
        /** @var CustomerUser $customerUser */
        $customerUser = $quote->getCustomerUser();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_view',
                ['id' => $customerUser->getId()]
            )
        );
        $gridAttr = $crawler->filter('[id^=grid-customer-user-view-quote-grid]')
            ->first()->attr('data-page-component-options');
        $gridJsonElements = \json_decode(\html_entity_decode($gridAttr), true);

        static::assertStringContainsString($quote->getOwner()->getFullName(), $gridAttr);
        $this->assertCount(
            \count(LoadQuoteData::getQuotesFor('customerUser', $customerUser->getEmail())),
            $gridJsonElements['data']['data']
        );
    }
}
