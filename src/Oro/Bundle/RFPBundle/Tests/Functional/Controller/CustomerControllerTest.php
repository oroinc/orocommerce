<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CustomerControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadRequestData::class]);
    }

    public function testQuoteGridOnCustomerViewPage()
    {
        /** @var Request $request */
        $request = self::getContainer()->get('doctrine')
            ->getRepository(Request::class)
            ->findOneBy(['note' => 'rfp.request.3']);
        /** @var Customer $customer */
        $customer = $request->getCustomerUser()->getCustomer();
        $urlCustomerCustomerView = $this->getUrl('oro_customer_customer_view', ['id' => $customer->getId()]);
        $crawler = $this->client->request('GET', $urlCustomerCustomerView);
        $gridAttr = $crawler->filter('[id^=grid-customer-view-rfq-grid]')->first()->attr('data-page-component-options');
        $gridJsonElements = self::jsonToArray(html_entity_decode($gridAttr));
        self::assertStringContainsString($request->getCustomerUser()->getFullName(), $gridAttr);
        self::assertCount(
            count(LoadRequestData::getRequestsFor('customer', $customer->getName())),
            $gridJsonElements['data']['data']
        );
    }
}
