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
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxOrderControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadOrders::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserAddresses::class
        ]);
    }

    private function assertTotal(Crawler $crawler, ?int $id = null): void
    {
        $form = $crawler->selectButton('Save and Close')->form();
        $form->getFormNode()->setAttribute('action', $this->getUrl('oro_order_entry_point', ['id' => $id]));

        $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());
        self::assertArrayHasKey('totals', $data);
        self::assertArrayHasKey('subtotals', $data['totals']);
        self::assertArrayHasKey(0, $data['totals']['subtotals']);
        self::assertArrayHasKey('total', $data['totals']);
    }

    public function testNewOrderSubtotals(): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_create')
        );

        $this->assertTotal($crawler);
    }

    public function testSubtotals(): void
    {
        $order = $this->getReference('simple_order');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_update', ['id' => $order->getId()])
        );

        $this->assertTotal($crawler, $order->getId());
    }

    /**
     * @dataProvider getRelatedDataActionDataProvider
     */
    public function testGetRelatedDataAction(string $customer, ?string $customerUser = null): void
    {
        /** @var Customer $order */
        $customerEntity = $this->getReference($customer);
        /** @var CustomerUser $order */
        $customerUserEntity = $customerUser ? $this->getReference($customerUser) : null;
        $website = self::getContainer()->get('oro_website.manager')->getDefaultWebsite();

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
        self::assertInstanceOf(JsonResponse::class, $response);

        $result = self::getJsonResponseContent($response, 200);
        self::assertArrayHasKey('billingAddress', $result);
        self::assertArrayHasKey('shippingAddress', $result);
        self::assertArrayHasKey('customerPaymentTerm', $result);
        self::assertArrayHasKey('customerGroupPaymentTerm', $result);
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
