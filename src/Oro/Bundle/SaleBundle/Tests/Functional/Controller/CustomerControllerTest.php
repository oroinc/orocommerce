<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

class CustomerControllerTest extends WebTestCase
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

    public function testQuoteGridOnCustomerViewPage()
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.3');
        /** @var Customer $customer */
        $customer = $quote->getCustomer();
        $urlCustomerCustomerView = $this->getUrl('oro_customer_customer_view', ['id' => $customer->getId()]);
        $crawler = $this->client->request('GET', $urlCustomerCustomerView);
        $gridAttr = $crawler->filter('[id^=grid-customer-view-quote-grid]')
            ->first()->attr('data-page-component-options');
        $this->assertContains($quote->getOwner()->getFullName(), $gridAttr);
        $this->assertContains($quote->getCustomerUser()->getFullName(), $gridAttr);
        $gridJsonElements = json_decode(html_entity_decode($gridAttr), true);
        $this->assertCount(
            count(LoadQuoteData::getQuotesFor('customer', $customer->getName())),
            $gridJsonElements['data']['data']
        );
    }
}
