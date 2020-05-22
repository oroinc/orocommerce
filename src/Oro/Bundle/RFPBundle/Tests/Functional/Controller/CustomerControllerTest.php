<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CustomerControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            'Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
        ]);
    }

    public function testQuoteGridOnCustomerViewPage()
    {
        $repo = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository('OroRFPBundle:Request');
        /** @var Request $request */
        $request = $repo->findOneBy(['note' => 'rfp.request.3']);
        /** @var Customer $customer */
        $customer = $request->getCustomerUser()->getCustomer();
        $urlCustomerCustomerView = $this->getUrl('oro_customer_customer_view', ['id' => $customer->getId()]);
        $crawler = $this->client->request('GET', $urlCustomerCustomerView);
        $gridAttr = $crawler->filter('[id^=grid-customer-view-rfq-grid]')->first()->attr('data-page-component-options');
        $gridJsonElements = json_decode(html_entity_decode($gridAttr), true);
        static::assertStringContainsString($request->getCustomerUser()->getFullName(), $gridAttr);
        $this->assertCount(
            count(LoadRequestData::getRequestsFor('customer', $customer->getName())),
            $gridJsonElements['data']['data']
        );
    }
}
