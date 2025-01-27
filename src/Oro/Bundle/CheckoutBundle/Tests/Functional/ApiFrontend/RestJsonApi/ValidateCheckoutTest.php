<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ValidateCheckoutTest extends FrontendRestJsonApiTestCase
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

    public function testTryToCreateWithDuplicatedLineItem(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            'create_checkout_with_duplicate_line_item.yml',
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Checkout should contain only unique line items.',
                    'source' => ['pointer' => '/included/2']
                ],
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Checkout should contain only unique line items.',
                    'source' => ['pointer' => '/included/3']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateWithDuplicatedLineItem(): void
    {
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>'],
            'update_checkout_with_duplicate_line_item.yml',
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'unique collection item constraint',
                'detail' => 'Checkout should contain only unique line items.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidBillingAddress(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'relationships' => [
                        'billingAddress' => [
                            'data' => ['type' => 'checkoutaddresses', 'id' => 'billing_address']
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
                                'data' => ['type' => 'countries', 'id' => '00']
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
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'valid checkout address constraint',
                    'detail' => 'Please enter correct billing address.',
                    'source' => ['pointer' => '/data/relationships/billingAddress/data']
                ],
                [
                    'title' => 'form constraint',
                    'detail' => 'The entity does not exist.',
                    'source' => ['pointer' => '/included/0/relationships/country/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidShippingAddress(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'relationships' => [
                        'shippingAddress' => [
                            'data' => ['type' => 'checkoutaddresses', 'id' => 'shipping_address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'checkoutaddresses',
                        'id' => 'shipping_address',
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
                                'data' => ['type' => 'countries', 'id' => '00']
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
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'valid checkout address constraint',
                    'detail' => 'Please enter correct shipping address.',
                    'source' => ['pointer' => '/data/relationships/shippingAddress/data']
                ],
                [
                    'title' => 'form constraint',
                    'detail' => 'The entity does not exist.',
                    'source' => ['pointer' => '/included/0/relationships/country/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidPaymentMethod(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        'paymentMethod' => 'invalid'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'payment method is applicable constraint',
                'detail' => 'The selected payment method is not available.'
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidShippingMethod(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        'shippingMethod' => 'invalid_method',
                        'shippingMethodType' => 'invalid_type'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'shipping method is valid constraint',
                'detail' => 'Shipping method is not valid.'
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidShippingMethodType(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        'shippingMethod' => '@checkout.completed->shippingMethod',
                        'shippingMethodType' => 'invalid_type'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'shipping method is valid constraint',
                'detail' => 'Shipping method type is not valid.'
            ],
            $response
        );
    }

    public function testTryToUpdateForCompleted(): void
    {
        $checkoutId = $this->getReference('checkout.completed')->getId();
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
                'title' => 'access denied exception',
                'detail' => 'The completed checkout cannot be changed.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateForPaymentInProgress(): void
    {
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $em = $this->getEntityManager();
        $em->find(Checkout::class, $checkoutId)->setPaymentInProgress(true);
        $em->flush();
        try {
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
                    'title' => 'access denied exception',
                    'detail' => 'The checkout cannot be changed as the payment is being processed.'
                ],
                $response,
                Response::HTTP_FORBIDDEN
            );
        } finally {
            $em->find(Checkout::class, $checkoutId)->setPaymentInProgress(false);
            $em->flush();
        }
    }

    public function testTryToUpdateForDeleted(): void
    {
        $checkoutId = $this->getReference('checkout.deleted')->getId();
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
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForLineItems(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'lineItems'],
            [
                'data' => [
                    ['type' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForLineItems(): void
    {
        $response = $this->postRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'lineItems'],
            [
                'data' => [
                    ['type' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForLineItems(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>', 'association' => 'lineItems'],
            [
                'data' => [
                    ['type' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>']
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipForSource(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.open->id)>', 'association' => 'source'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipForOrder(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.open->id)>', 'association' => 'order'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForOrder(): void
    {
        $response = $this->postRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.open->id)>', 'association' => 'order'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForOrder(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.open->id)>', 'association' => 'order'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToSetInvalidShippingMethod(): void
    {
        $checkoutId = $this->getReference('checkout.open')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'shippingMethod' => 'invalid_method'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'shipping method is valid constraint',
                'detail' => 'Shipping method is not valid.'
            ],
            $response
        );
    }

    public function testTryToSetInvalidShippingMethodType(): void
    {
        $checkoutId = $this->getReference('checkout.open')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'shippingMethod' => '@checkout.completed->shippingMethod',
                        'shippingMethodType' => 'invalid_type'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'shipping method is valid constraint',
                'detail' => 'Shipping method type is not valid.'
            ],
            $response
        );
    }

    public function testTryToSetInvalidPaymentMethod(): void
    {
        $checkoutId = $this->getReference('checkout.open')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'paymentMethod' => 'invalid_method'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'payment method is applicable constraint',
                'detail' => 'The selected payment method is not available.'
            ],
            $response
        );
    }

    public function testTryToSetInvalidBillingAddress(): void
    {
        $checkoutId = $this->getReference('checkout.open')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'relationships' => [
                        'billingAddress' => [
                            'data' => ['type' => 'checkoutaddresses', 'id' => 'billing_address']
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
                                'data' => ['type' => 'countries', 'id' => '00']
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
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'valid checkout address constraint',
                    'detail' => 'Please enter correct billing address.',
                    'source' => ['pointer' => '/data/relationships/billingAddress/data']
                ],
                [
                    'title' => 'form constraint',
                    'detail' => 'The entity does not exist.',
                    'source' => ['pointer' => '/included/0/relationships/country/data']
                ]
            ],
            $response
        );
    }

    public function testTryToSetInvalidShippingAddress(): void
    {
        $checkoutId = $this->getReference('checkout.open')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'relationships' => [
                        'shippingAddress' => [
                            'data' => ['type' => 'checkoutaddresses', 'id' => 'shipping_address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'checkoutaddresses',
                        'id' => 'shipping_address',
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
                                'data' => ['type' => 'countries', 'id' => '00']
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
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'valid checkout address constraint',
                    'detail' => 'Please enter correct shipping address.',
                    'source' => ['pointer' => '/data/relationships/shippingAddress/data']
                ],
                [
                    'title' => 'form constraint',
                    'detail' => 'The entity does not exist.',
                    'source' => ['pointer' => '/included/0/relationships/country/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithDuplicatedKitItemLineItem(): void
    {
        $response = $this->post(
            ['entity' => 'checkouts'],
            'create_checkout_with_duplicate_kit_item.yml',
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Product kit line item must contain only unique kit item line items.',
                    'source' => ['pointer' => '/included/5']
                ],
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Product kit line item must contain only unique kit item line items.',
                    'source' => ['pointer' => '/included/6']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateWithDuplicatedKitItemLineItem(): void
    {
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>'],
            'update_checkout_with_duplicate_kit_item.yml',
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Product kit line item must contain only unique kit item line items.',
                    'source' => ['pointer' => '/included/3']
                ],
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Product kit line item must contain only unique kit item line items.',
                    'source' => ['pointer' => '/included/4']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProductForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        unset($data['included'][3]['relationships']['product']);
        $response = $this->post(['entity' => 'checkouts'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'Please choose a product',
                'source' => ['pointer' => '/included/3/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProductUnitForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        unset($data['included'][3]['relationships']['productUnit']);
        $response = $this->post(['entity' => 'checkouts'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/included/3/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongProductUnitForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        $data['included'][3]['relationships']['productUnit']['data']['id'] = '<toString(@product_unit.bottle->code)>';
        $response = $this->post(['entity' => 'checkouts'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'product kit item line item product unit available constraint',
                'detail' => 'The selected product unit is not allowed',
                'source' => ['pointer' => '/included/3/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithFloatQuantityWhenPrecisionIsInvalidForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        $data['included'][3]['attributes']['quantity'] = 1.2345;
        $response = $this->post(['entity' => 'checkouts'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'product kit item line item quantity unit precision constraint',
                'detail' => 'Only 3 decimal digits are allowed for unit "liter"',
                'source' => ['pointer' => '/included/3/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutQuantityForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        $data['included'][3]['attributes']['quantity'] = null;
        $response = $this->post(['entity' => 'checkouts'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'The quantity should be greater than 0.',
                'source' => ['pointer' => '/included/3/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithNegativeQuantityForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        $data['included'][3]['attributes']['quantity'] = -1;
        $response = $this->post(['entity' => 'checkouts'], $data, [], false);
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'greater than constraint',
                    'detail' => 'The quantity should be greater than 0.',
                    'source' => ['pointer' => '/included/3/attributes/quantity']
                ],
                [
                    'title' => 'range constraint',
                    'detail' => 'The quantity should be between 1 and 2.',
                    'source' => ['pointer' => '/included/3/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithNonFloatQuantityForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        $data['included'][3]['attributes']['quantity'] = 'some string';
        $response = $this->post(['entity' => 'checkouts'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'Please enter a number.',
                'source' => ['pointer' => '/included/3/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutKitItemForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_with_product_kit.yml');
        unset($data['included'][3]['relationships']['kitItem']);
        $response = $this->post(['entity' => 'checkouts'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'Product kit item must not be blank',
                'source' => ['pointer' => '/included/3/relationships/kitItem/data']
            ],
            $response
        );
    }
}
