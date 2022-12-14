<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AjaxQuoteControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadQuoteData::class]);
    }

    /**
     * @dataProvider getRelatedDataActionDataProvider
     */
    public function testGetRelatedDataAction(?string $customer, ?string $customerUser)
    {
        /** @var Customer $order */
        $customerEntity = $customer ? $this->getReference($customer) : null;

        /** @var CustomerUser $order */
        $customerUserEntity = $customerUser ? $this->getReference($customerUser) : null;

        $this->client->request(
            'GET',
            $this->getUrl('oro_quote_related_data'),
            [
                QuoteType::NAME => [
                    'customer' => $customerEntity?->getId(),
                    'customerUser' => $customerUserEntity?->getId()
                ]
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('shippingAddress', $result);
        $this->assertArrayHasKey('customerPaymentTerm', $result);
        $this->assertArrayHasKey('customerGroupPaymentTerm', $result);
    }

    public function getRelatedDataActionDataProvider(): array
    {
        return [
            [
                'customer' => 'sale-customer1',
                'customerUser' => 'sale-customer1-user1@example.com'
            ],
            [
                'customer' => 'sale-customer1',
                'customerUser' => null
            ],
            [
                'customer' => null,
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
        $this->assertInstanceOf(Response::class, $response);

        $this->assertResponseStatusCodeEquals($response, 400);
    }

    public function testEntryPoint()
    {
        $this->ajaxRequest('POST', $this->getUrl('oro_quote_entry_point'));
        $response = $this->client->getResponse();

        self::assertInstanceOf(JsonResponse::class, $response);
    }

    public function testEntryPointAction()
    {
        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_quote_entry_point'),
            [
                QuoteType::NAME => [
                    'calculateShipping' => true
                ]
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('possibleShippingMethods', $result);
    }
}
