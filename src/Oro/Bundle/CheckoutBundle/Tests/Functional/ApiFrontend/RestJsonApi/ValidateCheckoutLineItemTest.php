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
class ValidateCheckoutLineItemTest extends FrontendRestJsonApiTestCase
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

    public function testTryToCreateDuplicatedLineItem(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutlineitems'],
            'create_checkout_line_item_duplicate.yml',
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'unique collection item constraint',
                'detail' => 'Checkout should contain only unique line items.'
            ],
            $response
        );
    }

    public function testTryToUpdateDuplicatedLineItem(): void
    {
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.3->id)>'],
            'update_checkout_line_item_duplicate.yml',
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'unique collection item constraint',
                'detail' => 'Checkout should contain only unique line items.'
            ],
            $response
        );
    }

    public function testTryToCreateWithoutRequiredFields(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutlineitems'],
            ['data' => ['type' => 'checkoutlineitems']],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not blank constraint',
                    'detail' => 'The quantity should be greater than 0.',
                    'source' => ['pointer' => '/data/attributes/quantity']
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/data/relationships/checkout/data']
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/data/relationships/product/data']
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/data/relationships/productUnit/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutCheckout(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_min.yml');
        unset($data['data']['relationships']['checkout']);
        $response = $this->post(
            ['entity' => 'checkoutlineitems'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/data/relationships/checkout/data']
            ],
            $response
        );
    }

    public function testTryToUpdateForCompletedCheckout(): void
    {
        $lineItemId = $this->getReference('checkout.completed.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 10
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

    public function testTryToUpdateForPaymentInProgressCheckout(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $checkoutId = $this->getReference('checkout.in_progress')->getId();
        $em = $this->getEntityManager();
        $em->find(Checkout::class, $checkoutId)->setPaymentInProgress(true);
        $em->flush();
        try {
            $response = $this->patch(
                ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
                [
                    'data' => [
                        'type' => 'checkoutlineitems',
                        'id' => (string)$lineItemId,
                        'attributes' => [
                            'quantity' => 10
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

    public function testTryToUpdateForDeletedCheckout(): void
    {
        $lineItemId = $this->getReference('checkout.deleted.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 10
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateShippingMethod(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'shippingMethod' => '@checkout.completed->shippingMethod',
                        'shippingMethodType' => '@checkout.completed->shippingMethodType'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'shipping method change constraint',
                    'detail' => 'This value can be changed only when the shipping type is "line_item".',
                    'source' => ['pointer' => '/data/attributes/shippingMethod']
                ],
                [
                    'title' => 'shipping method change constraint',
                    'detail' => 'This value can be changed only when the shipping type is "line_item".',
                    'source' => ['pointer' => '/data/attributes/shippingMethodType']
                ]
            ],
            $response
        );
    }

    public function testTryToGetSubresourceForCheckoutForDeletedCheckout(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.deleted.line_item.1->id)>',
                'association' => 'checkout'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForCheckoutForDeletedCheckout(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.deleted.line_item.1->id)>',
                'association' => 'checkout'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForCheckoutFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.another_department_customer_user.line_item.1->id)>',
                'association' => 'checkout'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetSubresourceForCheckoutFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.another_department_customer_user.line_item.1->id)>',
                'association' => 'checkout'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateRelationshipForCheckout(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'checkout'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateFromAnotherDepartment(): void
    {
        $lineItemId = $this->getReference('checkout.another_department_customer_user.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 10
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToUpdateRelationshipForKitItemLineItems(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'kitItemLineItems'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForKitItemLineItems(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'kitItemLineItems'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForKitItemLineItems(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'kitItemLineItems'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdatePriceWithInvalidValue(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'price' => 'invalid'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/price'],
            ],
            $response
        );
    }

    public function testTryToUpdateFloatQuantityWhenPrecisionIsZero(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 123.45
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'quantity unit precision constraint',
                'detail' => 'The precision for the unit "milliliter" is not valid.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToSetZeroQuantity(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 0
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'quantity to order constraint',
                    'detail' => 'You cannot order less than 1 units',
                    'source' => ['pointer' => '/data/attributes/quantity']
                ],
                [
                    'title' => 'expression constraint',
                    'detail' => 'The quantity should be greater than 0.',
                    'source' => ['pointer' => '/data/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testTryToSetNegativeQuantity(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => -10
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'quantity to order constraint',
                    'detail' => 'You cannot order less than 1 units',
                    'source' => ['pointer' => '/data/attributes/quantity']
                ],
                [
                    'title' => 'expression constraint',
                    'detail' => 'The quantity should be greater than 0.',
                    'source' => ['pointer' => '/data/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testTryToSetNullQuantity(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => null
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'quantity to order constraint',
                    'detail' => 'You cannot order less than 1 units',
                    'source' => ['pointer' => '/data/attributes/quantity']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'The quantity should be greater than 0.',
                    'source' => ['pointer' => '/data/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testTryToSetNullProductUnit(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'relationships' => [
                        'productUnit' => ['data' => null]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/data/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToSetNullProduct(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'relationships' => [
                        'product' => ['data' => null]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/data/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToSetNullCheckout(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'relationships' => [
                        'checkout' => ['data' => null]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/data/relationships/checkout/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithDuplicatedKitItemLineItem(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutlineitems'],
            'create_checkout_line_item_with_duplicate_kit_item.yml',
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Product kit line item must contain only unique kit item line items.',
                    'source' => ['pointer' => '/included/2']
                ],
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Product kit line item must contain only unique kit item line items.',
                    'source' => ['pointer' => '/included/3']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateWithDuplicatedKitItemLineItem(): void
    {
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.3->id)>'],
            'update_checkout_line_item_with_duplicate_kit_item.yml',
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Product kit line item must contain only unique kit item line items.',
                    'source' => ['pointer' => '/included/2']
                ],
                [
                    'title' => 'unique collection item constraint',
                    'detail' => 'Product kit line item must contain only unique kit item line items.',
                    'source' => ['pointer' => '/included/3']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProductForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_with_product_kit.yml');
        unset($data['included'][0]['relationships']['product']);
        $response = $this->post(['entity' => 'checkoutlineitems'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'Please choose a product',
                'source' => ['pointer' => '/included/0/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProductUnitForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_with_product_kit.yml');
        unset($data['included'][0]['relationships']['productUnit']);
        $response = $this->post(['entity' => 'checkoutlineitems'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'This value should not be null.',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongProductUnitForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_with_product_kit.yml');
        $data['included'][0]['relationships']['productUnit']['data']['id'] = '<toString(@product_unit.bottle->code)>';
        $response = $this->post(['entity' => 'checkoutlineitems'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'product kit item line item product unit available constraint',
                'detail' => 'The selected product unit is not allowed',
                'source' => ['pointer' => '/included/0/relationships/productUnit/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithFloatQuantityWhenPrecisionIsInvalidForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_with_product_kit.yml');
        $data['included'][0]['attributes']['quantity'] = 1.2345;
        $response = $this->post(['entity' => 'checkoutlineitems'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'product kit item line item quantity unit precision constraint',
                'detail' => 'Only 3 decimal digits are allowed for unit "liter"',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutQuantityForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_with_product_kit.yml');
        $data['included'][0]['attributes']['quantity'] = null;
        $response = $this->post(['entity' => 'checkoutlineitems'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'The quantity should be greater than 0.',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithNegativeQuantityForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_with_product_kit.yml');
        $data['included'][0]['attributes']['quantity'] = -1;
        $response = $this->post(['entity' => 'checkoutlineitems'], $data, [], false);
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'greater than constraint',
                    'detail' => 'The quantity should be greater than 0.',
                    'source' => ['pointer' => '/included/0/attributes/quantity']
                ],
                [
                    'title' => 'range constraint',
                    'detail' => 'The quantity should be between 1 and 2.',
                    'source' => ['pointer' => '/included/0/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithNonFloatQuantityForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_with_product_kit.yml');
        $data['included'][0]['attributes']['quantity'] = 'some string';
        $response = $this->post(['entity' => 'checkoutlineitems'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'Please enter a number.',
                'source' => ['pointer' => '/included/0/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutKitItemForKitItemLineItem(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_with_product_kit.yml');
        unset($data['included'][0]['relationships']['kitItem']);
        $response = $this->post(['entity' => 'checkoutlineitems'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'not null constraint',
                'detail' => 'Product kit item must not be blank',
                'source' => ['pointer' => '/included/0/relationships/kitItem/data']
            ],
            $response
        );
    }
}
