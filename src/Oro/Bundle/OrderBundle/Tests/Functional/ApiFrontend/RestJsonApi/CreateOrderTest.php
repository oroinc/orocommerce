<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CreateOrderTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
            LoadPaymentTermData::class
        ]);

        $this->getOptionalListenerManager()
            ->enableListener('oro_order.order.listener.orm.order_shipping_status_listener');
    }

    #[\Override]
    protected function postFixtureLoad(): void
    {
        parent::postFixtureLoad();
        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($this->getReference('customer'), $this->getReference('payment_term_net_10'));
        $this->getEntityManager()->flush();
    }

    private function updateOrderResponseContent(array|string $expectedContent, Response $response): array
    {
        return $this->updateResponseContent(
            $this->updateResponseContent($expectedContent, $response),
            $response,
            'identifier'
        );
    }

    private function generateLineItemChecksum(OrderLineItem $lineItem): string
    {
        /** @var LineItemChecksumGeneratorInterface $lineItemChecksumGenerator */
        $lineItemChecksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');
        $checksum = $lineItemChecksumGenerator->getChecksum($lineItem);
        self::assertNotEmpty($checksum, 'Impossible to generate the line item checksum.');

        return $checksum;
    }

    public function testCreate(): void
    {
        $shipUntil = (new \DateTime('now + 10 day'))->format('Y-m-d');
        $data = $this->getRequestData('create_order.yml');
        $data['data']['attributes']['shipUntil'] = $shipUntil;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        $responseContent = $this->getResponseData('create_order.yml');
        $responseContent['data']['attributes']['shipUntil'] = $shipUntil;
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        // the status should be read-only when "Enable External Status Management" configuration option is disabled
        self::assertNull($order->getStatus());
        $lineItem = $order->getLineItems()->first();
        self::assertEquals($this->generateLineItemChecksum($lineItem), $lineItem->getChecksum());
    }

    public function testTryToCreateWithMinimumOrderAmountNotMet(): void
    {
        $shipUntil = (new \DateTime('now + 10 day'))->format('Y-m-d');
        $data = $this->getRequestData('create_order.yml');
        $data['data']['attributes']['shipUntil'] = $shipUntil;

        $configManager = self::getConfigManager();
        $initialMinimumOrderAmount = $configManager->get('oro_checkout.minimum_order_amount');
        $configManager->set('oro_checkout.minimum_order_amount', [['value' => '112.55', 'currency' => 'USD']]);
        $configManager->flush();
        try {
            $response = $this->post(['entity' => 'orders'], $data, [], false);
        } finally {
            $configManager->set('oro_checkout.minimum_order_amount', $initialMinimumOrderAmount);
            $configManager->flush();
        }

        $this->assertResponseValidationError(
            [
                'title'  => 'order limit amounts constraint',
                'detail' =>
                    'A minimum order subtotal of $112.55 is required to check out. Please add $90.75 more to proceed.',
            ],
            $response
        );
    }

    public function testTryToCreateWithMaximumOrderAmountNotMet(): void
    {
        $shipUntil = (new \DateTime('now + 10 day'))->format('Y-m-d');
        $data = $this->getRequestData('create_order.yml');
        $data['data']['attributes']['shipUntil'] = $shipUntil;

        $configManager = self::getConfigManager();
        $initialMaximumOrderAmount = $configManager->get('oro_checkout.maximum_order_amount');
        $configManager->set('oro_checkout.maximum_order_amount', [['value' => '11.55', 'currency' => 'USD']]);
        $configManager->flush();
        try {
            $response = $this->post(['entity' => 'orders'], $data, [], false);
        } finally {
            $configManager->set('oro_checkout.maximum_order_amount', $initialMaximumOrderAmount);
            $configManager->flush();
        }

        $this->assertResponseValidationError(
            [
                'title'  => 'order limit amounts constraint',
                'detail' =>
                    'The order subtotal cannot exceed $11.55. Please remove at least $10.25 to proceed.',
            ],
            $response
        );
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $response = $this->post(
            ['entity' => 'orders'],
            'create_order_min.yml'
        );

        $orderId = (int)$this->getResourceId($response);

        $responseContent = $this->updateOrderResponseContent('create_order_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        $lineItem = $order->getLineItems()->first();
        self::assertEquals($this->generateLineItemChecksum($lineItem), $lineItem->getChecksum());
    }

    public function testCreateWithCurrency(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['data']['attributes']['currency'] = 'EUR';

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->updateOrderResponseContent('create_order_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithoutProductRelationshipButWithProductSku(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['productSku'] = '@product1->sku';
        $data['included'][3]['attributes']['productSku'] = '@product-kit-1->sku';
        unset(
            $data['included'][0]['relationships']['product'],
            $data['included'][3]['relationships']['product']
        );

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->updateOrderResponseContent('create_order_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithCustomerUserAddressesAsOrderAddresses(): void
    {
        $response = $this->post(
            ['entity' => 'orders'],
            'create_order_customer_user_addresses.yml'
        );

        $responseContent = $this->updateResponseContent('create_order_customer_user_addresses.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithFilledBillingAddressData(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][1] = $this->getRequestData('order_address_data.yml');

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->getResponseData('create_order_min.yml');
        $responseContent['included'][1]['relationships']['customerAddress']['data'] = null;
        $responseContent = $this->updateOrderResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithShippingStatus(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['data']['relationships']['shippingStatus']['data'] = [
            'type' => 'ordershippingstatuses',
            'id' => 'shipped'
        ];

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->updateOrderResponseContent('create_order_min.yml', $response);
        $responseContent['data']['relationships']['shippingStatus']['data'] = [
            'type' => 'ordershippingstatuses',
            'id' => 'shipped'
        ];

        $orderId = (int)$this->getResourceId($response);

        $this->assertResponseContains($responseContent, $response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('shipped', $order->getShippingStatus()->getInternalId());
    }

    public function testTryToCreateWithCreatedAtAndUpdatedAt(): void
    {
        $createdAt = (new \DateTime('now - 10 day'))->format('Y-m-d\TH:i:s\Z');
        $updatedAt = (new \DateTime('now - 9 day'))->format('Y-m-d\TH:i:s\Z');
        $data = $this->getRequestData('create_order_min.yml');
        $data['data']['attributes']['createdAt'] = $createdAt;
        $data['data']['attributes']['updatedAt'] = $updatedAt;
        $data['included'][0]['attributes']['createdAt'] = $createdAt;
        $data['included'][0]['attributes']['updatedAt'] = $updatedAt;

        $response = $this->post(['entity' => 'orders'], $data);

        $orderId = (int)$this->getResourceId($response);

        $responseContent = $this->updateOrderResponseContent('create_order_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        // createdAt and updatedAt fields are read-only for orders and line items
        self::assertNotEquals($createdAt, $order->getCreatedAt()->format('Y-m-d\TH:i:s\Z'));
        self::assertNotEquals($updatedAt, $order->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'));
        foreach ($order->getLineItems() as $lineItem) {
            self::assertNotEquals($createdAt, $lineItem->getCreatedAt()->format('Y-m-d\TH:i:s\Z'));
            self::assertNotEquals($updatedAt, $lineItem->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'));
        }
    }

    public function testTryToCreateEmpty(): void
    {
        $data = [
            'data' => [
                'type' => 'orders'
            ]
        ];

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/billingAddress/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/shippingAddress/data']
                ],
                [
                    'title'  => 'count constraint',
                    'detail' => 'Please add at least one Line Item',
                    'source' => ['pointer' => '/data/relationships/lineItems/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProduct(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        unset(
            $data['included'][0]['relationships']['product'],
            $data['included'][3]['relationships']['product']
        );

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'line item product constraint',
                    'detail' => 'Please choose Product.',
                    'source' => ['pointer' => '/included/0/relationships/product/data']
                ],
                [
                    'title'  => 'line item product constraint',
                    'detail' => 'Please choose Product.',
                    'source' => ['pointer' => '/included/3/relationships/product/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithFreeFormProduct(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['freeFormProduct'] = 'test';
        unset($data['included'][0]['relationships']['product']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'line item product constraint',
                'detail' => 'Please choose Product.',
                'source' => ['pointer' => '/included/0/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProductUnit(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        unset(
            $data['included'][0]['relationships']['productUnit'],
            $data['included'][3]['relationships']['productUnit']
        );

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'The product unit does not exist for the product.',
                    'source' => ['pointer' => '/included/0/relationships/productUnit/data']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'The product unit does not exist for the product.',
                    'source' => ['pointer' => '/included/3/relationships/productUnit/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongProductUnit(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product2->id)>';
        $data['included'][0]['relationships']['productUnit']['data']['id'] = '<toString(@set->code)>';
        $data['included'][3]['relationships']['productUnit']['data']['id'] = '<toString(@set->code)>';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'product unit exists constraint',
                    'detail' => 'The product unit does not exist for the product.',
                    'source' => ['pointer' => '/included/0/relationships/productUnit/data']
                ],
                [
                    'title'  => 'product unit exists constraint',
                    'detail' => 'The product unit does not exist for the product.',
                    'source' => ['pointer' => '/included/3/relationships/productUnit/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithNotSellProductProductUnit(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product3->id)>';
        $data['included'][0]['relationships']['productUnit']['data']['id'] = '<toString(@set->code)>';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'product unit exists constraint',
                'detail' => 'The product unit does not exist for the product.',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithFloatQuantityWhenPrecisionIsZero(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['quantity'] = 123.45;
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product2->id)>';
        $data['included'][3]['attributes']['quantity'] = 123.45;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'quantity unit precision constraint',
                    'detail' => 'The precision for the unit "item" is not valid.',
                    'source' => ['pointer' => '/included/0/attributes/quantity']
                ],
                [
                    'title'  => 'quantity unit precision constraint',
                    'detail' => 'The precision for the unit "milliliter" is not valid.',
                    'source' => ['pointer' => '/included/3/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutQuantity(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        foreach ([0, 3] as $key) {
            unset($data['included'][$key]['attributes']['quantity']);
            if (!$data['included'][$key]['attributes']) {
                unset($data['included'][$key]['attributes']);
            }
        }

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/0/attributes/quantity']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/3/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithNegativeQuantity(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['quantity'] = -1;
        $data['included'][3]['attributes']['quantity'] = -1;
        $data['included'][4]['attributes']['quantity'] = -1;

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'greater than constraint',
                    'detail' => 'This value should be greater than 0.',
                    'source' => ['pointer' => '/included/0/attributes/quantity']
                ],
                [
                    'title'  => 'greater than constraint',
                    'detail' => 'This value should be greater than 0.',
                    'source' => ['pointer' => '/included/3/attributes/quantity']
                ],
                [
                    'title'  => 'greater than constraint',
                    'detail' => 'The quantity should be greater than 0',
                    'source' => ['pointer' => '/included/4/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWhenPaymentMethodWasNotFound(): void
    {
        $this->getReference('payment_term_rule')->setEnabled(false);
        $this->getEntityManager()->flush();

        $response = $this->post(
            ['entity' => 'orders'],
            'create_order_min.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'payment method constraint',
                'detail' => 'No payment methods are available, please contact us to complete the order submission.'
            ],
            $response
        );
    }

    public function testTryToCreateWithSubmittedPriceThatNotEqualsToCalculatedPrice(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['price'] = '9999.9';
        $data['included'][3]['attributes']['price'] = '9999.9';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'price match constraint',
                    'detail' => 'The specified price must be equal to 1.01.',
                    'source' => ['pointer' => '/included/0/attributes/price']
                ],
                [
                    'title'  => 'price match constraint',
                    'detail' => 'The specified price must be equal to 11.59.',
                    'source' => ['pointer' => '/included/3/attributes/price']
                ]
            ],
            $response
        );
    }

    public function testCreateWithSubmittedPriceThatEqualsToCalculatedPrice(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['price'] = '1.0100';
        $data['included'][0]['attributes']['currency'] = 'USD';
        $data['included'][3]['attributes']['price'] = '11.5900';
        $data['included'][3]['attributes']['currency'] = 'USD';

        $response = $this->post(['entity' => 'orders'], $data);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
    }

    public function testCreateWithSubmittedNullPrice(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['price'] = null;
        $data['included'][3]['attributes']['price'] = null;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][0]['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][1]['id'] = 'new';
        $expectedData['data']['relationships']['billingAddress']['data']['id'] = 'new';
        $expectedData['data']['relationships']['shippingAddress']['data']['id'] = 'new';
        $expectedData['included'][0]['id'] = 'new';
        $expectedData['included'][0]['attributes']['price'] = '1.0100';
        $expectedData['included'][3]['attributes']['price'] = '11.5900';
        $expectedData['included'][1]['id'] = 'new';
        $expectedData['included'][2]['id'] = 'new';
        $expectedData['included'][3]['id'] = 'new';
        $expectedData['included'][3]['relationships']['kitItemLineItems']['data'][0]['id'] = 'new';
        $expectedData['included'][4]['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateWithSubmittedCurrencyThatNotEqualsToCalculatedCurrency(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['currency'] = 'EUR';
        $data['included'][3]['attributes']['currency'] = 'EUR';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'currency match constraint',
                    'detail' => 'The specified currency must be equal to "USD".',
                    'source' => ['pointer' => '/included/0/attributes/currency']
                ],
                [
                    'title'  => 'currency match constraint',
                    'detail' => 'The specified currency must be equal to "USD".',
                    'source' => ['pointer' => '/included/3/attributes/currency']
                ]
            ],
            $response
        );
    }

    public function testCreateWithSubmittedNullCurrency(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['currency'] = null;
        $data['included'][3]['attributes']['currency'] = null;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][0]['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][1]['id'] = 'new';
        $expectedData['data']['relationships']['billingAddress']['data']['id'] = 'new';
        $expectedData['data']['relationships']['shippingAddress']['data']['id'] = 'new';
        $expectedData['included'][0]['id'] = 'new';
        $expectedData['included'][0]['attributes']['currency'] = 'USD';
        $expectedData['included'][3]['attributes']['currency'] = 'USD';
        $expectedData['included'][1]['id'] = 'new';
        $expectedData['included'][2]['id'] = 'new';
        $expectedData['included'][3]['id'] = 'new';
        $expectedData['included'][3]['relationships']['kitItemLineItems']['data'][0]['id'] = 'new';
        $expectedData['included'][4]['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateWithProductWithoutPrice(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['relationships']['product']['data']['id'] = '<toString(@product3->id)>';

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'price not found constraint',
                'detail' => 'No matching price found.',
                'source' => ['pointer' => '/included/0/attributes/price']
            ],
            $response
        );
    }

    public function testTryToCreateWithDocuments(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['attributes']['documents'] = [
            ['mimeType' => 'text/plain', 'url' => 'some_file.txt']
        ];

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        $responseContent = $this->getResponseData('create_order.yml');
        $responseContent['data']['attributes']['documents'] = [];
        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(0, $order->getDocuments());
    }
}
