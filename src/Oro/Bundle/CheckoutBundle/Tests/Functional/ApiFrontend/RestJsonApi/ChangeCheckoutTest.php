<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChangeCheckoutTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadCompetedCheckoutData::class
        ]);
    }

    private function generateLineItemChecksum(CheckoutLineItem $lineItem): string
    {
        /** @var LineItemChecksumGeneratorInterface $lineItemChecksumGenerator */
        $lineItemChecksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');
        $checksum = $lineItemChecksumGenerator->getChecksum($lineItem);
        self::assertNotEmpty($checksum, 'Impossible to generate the line item checksum.');

        return $checksum;
    }

    public function testCreateEmpty(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            ['data' => ['type' => 'checkouts']]
        );

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        $this->getReferenceRepository()->setReference('created_checkout', $checkout);
        $this->assertResponseContains('create_checkout_empty.yml', $response);
    }

    public function testTryToCreateWithSource(): void
    {
        $shoppingListId = $this->getReference('checkout.in_progress.shopping_list')->getId();
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'relationships' => [
                        'source' => [
                            'data' => [
                                'type' => 'shoppinglists',
                                'id' => (string)$shoppingListId
                            ]
                        ]
                    ]
                ]
            ]
        );

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        $this->getReferenceRepository()->setReference('created_checkout', $checkout);
        $this->assertResponseContains('create_checkout_empty.yml', $response);
    }

    public function testTryToCreateWithOrder(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'relationships' => [
                        'order' => [
                            'data' => ['type' => 'orders', 'id' => '<toString(@checkout.completed.order->id)>']
                        ]
                    ]
                ]
            ]
        );

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        $this->getReferenceRepository()->setReference('created_checkout', $checkout);
        $this->assertResponseContains('create_checkout_empty.yml', $response);
    }

    public function testTryToCreateWithShippingType(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        'shippingType' => 'line_item'
                    ]
                ]
            ]
        );

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        $this->getReferenceRepository()->setReference('created_checkout', $checkout);
        $this->assertResponseContains('create_checkout_empty.yml', $response);
    }

    public function testCreateWithBillingAddress(): void
    {
        $data = [
            'data' => [
                'type' => 'checkouts',
                'relationships' => [
                    'billingAddress' => [
                        'data' => [
                            'type' => 'checkoutaddresses',
                            'id' => 'billing_address'
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'checkoutaddresses',
                    'id' => 'billing_address',
                    'attributes' => [
                        'label' => 'Address',
                        'street' => 'Street',
                        'city' => 'Los Angeles',
                        'postalCode' => '90001',
                        'organization' => 'Acme',
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'phone' => '123-456'
                    ],
                    'relationships' => [
                        'country' => [
                            'data' => [
                                'type' => 'countries',
                                'id' => '<toString(@country_usa->iso2Code)>'
                            ]
                        ],
                        'region' => [
                            'data' => [
                                'type' => 'regions',
                                'id' => '<toString(@region_usa_california->combinedCode)>'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'checkouts'], $data);

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);
        self::assertNotNull($checkout->getBillingAddress());
        self::assertFalse($checkout->isShipToBillingAddress());

        $this->getReferenceRepository()->setReference('created_checkout', $checkout);
        $expectedResponseData = $data;
        $billingAddressId = (string)$checkout->getBillingAddress()->getId();
        $expectedResponseData['data']['attributes']['shipToBillingAddress'] = false;
        $expectedResponseData['data']['relationships']['billingAddress']['data']['id'] = $billingAddressId;
        $expectedResponseData['included'][0]['id'] = $billingAddressId;
        $this->assertResponseContains($expectedResponseData, $response);
    }

    public function testCreateWithFullData(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            'create_checkout_with_full_data.yml'
        );

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);
        self::assertCount(1, $checkout->getLineItems());

        $expectedResponseData = $this->updateResponseContent('create_checkout_with_full_data.yml', $response);
        $expectedResponseData['included'][2]['attributes']['checksum'] = $this->generateLineItemChecksum(
            $checkout->getLineItems()->first()
        );
        $this->assertResponseContains($expectedResponseData, $response);
    }

    public function testCreateWithProductKit(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            'create_checkout_with_product_kit.yml'
        );

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);
        self::assertCount(1, $checkout->getLineItems());

        $expectedResponseData = $this->updateResponseContent('create_checkout_with_product_kit.yml', $response);
        $expectedResponseData['included'][2]['attributes']['checksum'] = $this->generateLineItemChecksum(
            $checkout->getLineItems()->first()
        );
        $this->assertResponseContains($expectedResponseData, $response);
    }

    public function testCreateWithProductKitAndNullPriceAndCurrency(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        // lineitem1 price
        $data['included'][2]['attributes']['price'] = null;
        $data['included'][2]['attributes']['currency'] = null;
        // checkoutproductkititemlineitem1 price
        $data['included'][3]['attributes']['price'] = null;
        $data['included'][3]['attributes']['currency'] = null;
        $response = $this->post(['entity' => 'checkouts'], $data);

        $checkoutId = (int)$this->getResourceId($response);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);
        self::assertCount(1, $checkout->getLineItems());

        $expectedResponseData = $this->updateResponseContent('create_checkout_with_product_kit.yml', $response);
        $expectedResponseData['included'][2]['attributes']['checksum'] = $this->generateLineItemChecksum(
            $checkout->getLineItems()->first()
        );
        $this->assertResponseContains($expectedResponseData, $response);
    }

    public function testCreateWhenDefaultShippingMethodShouldBeSet(): void
    {
        $data = $this->getRequestData('create_checkout_with_full_data.yml');
        unset($data['data']['attributes']['shippingMethod'], $data['data']['attributes']['shippingMethodType']);
        $response = $this->post(
            ['entity' => 'checkouts'],
            $data
        );

        $checkout = $this->getEntityManager()->find(Checkout::class, (int)$this->getResourceId($response));
        self::assertNotNull($checkout);

        self::assertEquals(
            $this->getReference('checkout.completed')->getShippingMethod(),
            $checkout->getShippingMethod()
        );
        self::assertEquals('primary', $checkout->getShippingMethodType());
        self::assertEquals([], $checkout->getLineItemGroupShippingData());
        foreach ($checkout->getLineItems() as $lineItem) {
            self::assertEquals(null, $lineItem->getShippingMethod());
            self::assertEquals(null, $lineItem->getShippingMethodType());
        }
    }

    public function testTryToUpdateSource(): void
    {
        $checkoutId = $this->getReference('checkout.empty')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'relationships' => [
                        'source' => [
                            'data' => [
                                'type' => 'shoppinglists',
                                'id' => '<toString(@checkout.completed.shopping_list->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'relationships' => [
                        'source' => ['data' => null]
                    ]
                ]
            ],
            $response
        );

        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNull($checkout->getSource()->getEntity());
    }

    public function testTryToUpdateOrder(): void
    {
        $checkoutId = $this->getReference('checkout.open')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'relationships' => [
                        'order' => [
                            'data' => ['type' => 'orders', 'id' => '<toString(@checkout.completed.order->id)>']
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'relationships' => [
                        'order' => ['data' => null]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateShippingType(): void
    {
        $checkoutId = $this->getReference('checkout.open')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'shippingType' => 'line_item'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'shippingType' => 'checkout'
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateWithFullData(): void
    {
        $checkoutId = $this->getReference('checkout.empty')->getId();

        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            'update_checkout_with_full_data.yml'
        );

        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);
        self::assertCount(1, $checkout->getLineItems());

        $expectedResponseData = $this->updateResponseContent('update_checkout_with_full_data.yml', $response);
        $expectedResponseData['included'][2]['attributes']['checksum'] = $this->generateLineItemChecksum(
            $checkout->getLineItems()->first()
        );
        $this->assertResponseContains($expectedResponseData, $response);
    }

    public function testUpdateWithProductKit(): void
    {
        $checkoutId = $this->getReference('checkout.empty')->getId();

        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            'update_checkout_with_product_kit.yml'
        );

        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);
        self::assertCount(1, $checkout->getLineItems());

        $expectedResponseData = $this->updateResponseContent('update_checkout_with_product_kit.yml', $response);
        $expectedResponseData['included'][2]['attributes']['checksum'] = $this->generateLineItemChecksum(
            $checkout->getLineItems()->first()
        );
        $this->assertResponseContains($expectedResponseData, $response);
    }

    public function testUpdateWithProductKitAndNullPriceAndCurrency(): void
    {
        $checkoutId = $this->getReference('checkout.empty')->getId();

        $data = $this->getRequestData('update_checkout_with_product_kit.yml');
        // lineitem1 price
        $data['included'][2]['attributes']['price'] = null;
        $data['included'][2]['attributes']['currency'] = null;
        // checkoutproductkititemlineitem1 price
        $data['included'][3]['attributes']['price'] = null;
        $data['included'][3]['attributes']['currency'] = null;
        $response = $this->patch(['entity' => 'checkouts', 'id' => (string)$checkoutId], $data);

        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);
        self::assertCount(1, $checkout->getLineItems());

        $expectedResponseData = $this->updateResponseContent('update_checkout_with_product_kit.yml', $response);
        $expectedResponseData['included'][2]['attributes']['checksum'] = $this->generateLineItemChecksum(
            $checkout->getLineItems()->first()
        );
        $this->assertResponseContains($expectedResponseData, $response);
    }

    public function testUpdateWhenDefaultShippingMethodShouldBeSet(): void
    {
        $checkoutId = $this->getReference('checkout.empty')->getId();

        $data = $this->getRequestData('update_checkout_with_full_data.yml');
        unset($data['data']['attributes']['shippingMethod'], $data['data']['attributes']['shippingMethodType']);
        $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            $data
        );

        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        self::assertEquals(
            $this->getReference('checkout.completed')->getShippingMethod(),
            $checkout->getShippingMethod()
        );
        self::assertEquals('primary', $checkout->getShippingMethodType());
        self::assertEquals([], $checkout->getLineItemGroupShippingData());
        foreach ($checkout->getLineItems() as $lineItem) {
            self::assertEquals(null, $lineItem->getShippingMethod());
            self::assertEquals(null, $lineItem->getShippingMethodType());
        }
    }

    public function testUpdateFromAnotherCustomerUser(): void
    {
        $checkoutId = $this->getReference('checkout.another_customer_user')->getId();
        $data = [
            'data' => [
                'type' => 'checkouts',
                'id' => (string)$checkoutId,
                'attributes' => [
                    'poNumber' => 'new_po_number'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            $data
        );
        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateFromAnotherDepartment(): void
    {
        $checkoutId = $this->getReference('checkout.another_department_customer_user')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'poNumber' => 'new_po_number'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDelete(): void
    {
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $this->delete(['entity' => 'checkouts', 'id' => (string)$checkoutId]);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertTrue(null === $checkout);
    }

    public function testDeleteFromAnotherCustomerUser(): void
    {
        $checkoutId = $this->getReference('checkout.another_customer_user')->getId();
        $this->delete(['entity' => 'checkouts', 'id' => (string)$checkoutId]);
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertTrue(null === $checkout);
    }

    public function testTryToDeleteFromAnotherDepartment(): void
    {
        $response = $this->delete(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.another_department_customer_user->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDeleteList(): void
    {
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $this->cdelete(
            ['entity' => 'checkouts'],
            ['filter' => ['id' => (string)$checkoutId]]
        );
        $checkoutId = $this->getEntityManager()->find(CheckoutLineItem::class, $checkoutId);
        self::assertTrue(null === $checkoutId);
    }
}
