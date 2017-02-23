<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxQuoteControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData'
            ]
        );
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

        $this->client->request(
            'GET',
            $this->getUrl('oro_quote_related_data'),
            [
                QuoteType::NAME => [
                    'customer' => $customerEntity->getId(),
                    'customerUser' => $customerUserEntity ? $customerUserEntity->getId() : null
                ]
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('shippingAddress', $result);
        $this->assertArrayHasKey('customerPaymentTerm', $result);
        $this->assertArrayHasKey('customerGroupPaymentTerm', $result);
    }

    /**
     * @return array
     */
    public function getRelatedDataActionDataProvider()
    {
        return [
            [
                'customer' => 'sale-customer1',
                'customerUser' => 'sale-customer1-user1@example.com'
            ],
            [
                'customer' => 'sale-customer1',
                'customerUser' => null
            ]
        ];
    }

    public function testGetRelatedDataActionException()
    {
        /** @var CustomerUser $customerUser1 */
        $customerUser1 = $this->getReference('sale-customer1-user1@example.com');

        /** @var CustomerUser $customerUser2 */
        $customerUser2 = $this->getReference('sale-customer2-user1@example.com');

        $this->client->request(
            'GET',
            $this->getUrl('oro_quote_related_data'),
            [
                QuoteType::NAME => [
                    'customer' => $customerUser1->getCustomer()->getId(),
                    'customerUser' => $customerUser2->getId(),
                ]
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);

        $this->assertResponseStatusCodeEquals($response, 400);
    }

    public function testEntryPoint()
    {
        $this->client->request('GET', $this->getUrl('oro_quote_entry_point'));
        $response = $this->client->getResponse();

        static::assertInstanceOf(JsonResponse::class, $response);
    }

    public function testEntryPointAction()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_quote_entry_point'),
            [
                QuoteType::NAME => [
                    'calculateShipping' => true
                ]
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('possibleShippingMethods', $result);
    }
}
