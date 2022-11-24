<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class AjaxOrderControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadOrders::class,
                LoadCustomerAddresses::class,
                LoadCustomerUserAddresses::class
            ]
        );
    }

    public function testNewOrderSubtotals()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_create')
        );

        $this->assertTotal($crawler);
    }

    public function testSubtotals()
    {
        $order = $this->getReference('simple_order');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_update', ['id' => $order->getId()])
        );

        $this->assertTotal($crawler, $order->getId());
    }

    /**
     * @param Crawler $crawler
     * @param null|int $id
     */
    private function assertTotal(Crawler $crawler, $id = null)
    {
        $form = $crawler->selectButton('Save and Close')->form();

        $form->getFormNode()->setAttribute('action', $this->getUrl('oro_order_entry_point', ['id' => $id]));

        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('subtotals', $data['totals']);
        $this->assertArrayHasKey(0, $data['totals']['subtotals']);
        $this->assertArrayHasKey('total', $data['totals']);
    }

    /**
     * @dataProvider getRelatedDataActionDataProvider
     *
     * @param string $customer
     * @param string|null $customerUser
     */
    public function testGetRelatedDataAction($customer, $customerUser = null)
    {
        /** @var Customer $order */
        $customerEntity = $this->getReference($customer);

        /** @var CustomerUser $order */
        $customerUserEntity = $customerUser ? $this->getReference($customerUser) : null;

        $website = $this->getContainer()->get('oro_website.manager')->getDefaultWebsite();

        $this->client->request(
            'GET',
            $this->getUrl('oro_order_entry_point'),
            [
                OrderType::NAME => [
                    'customer' => $customerEntity->getId(),
                    'website' => $website->getId(),
                    'customerUser' => $customerUserEntity ? $customerUserEntity->getId() : null
                ]
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('billingAddress', $result);
        $this->assertArrayHasKey('shippingAddress', $result);
        $this->assertArrayHasKey('customerPaymentTerm', $result);
        $this->assertArrayHasKey('customerGroupPaymentTerm', $result);
    }

    public function getRelatedDataActionDataProvider(): array
    {
        return [
            [
                'customer' => 'customer.level_1',
                'customerUser' => 'grzegorz.brzeczyszczykiewicz@example.com'
            ],
            [
                'customer' => 'customer.level_1',
                'customerUser' => null
            ]
        ];
    }
}
