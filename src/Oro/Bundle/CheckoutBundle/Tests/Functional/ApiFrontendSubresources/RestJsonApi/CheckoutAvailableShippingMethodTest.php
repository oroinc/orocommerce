<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendSubresources\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutAvailableShippingMethodTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class
        ]);
    }

    public function testCheckoutGetSubresource(): void
    {
        $response = $this->getSubresource([
            'entity' => 'checkouts',
            'id' => '<toString(@checkout.open->id)>',
            'association' => 'availableShippingMethods'
        ]);

        $expectedResponseData = $this->updateResponseContent([
            'data' => [
                [
                    'type' => 'checkoutavailableshippingmethods',
                    'id' => 'shipping_method',
                    'attributes' => [
                        'label' => 'Flat Rate',
                        'types' => [
                            [
                                'id' => 'primary',
                                'label' => 'Flat Rate',
                                'shippingCost' => '10.0000',
                                'currency' => 'USD'
                            ]
                        ]
                    ]
                ]
            ]
        ], $response, 'id', 'shipping_method');
        $this->assertResponseContains($expectedResponseData, $response);
    }

    public function testCheckoutGetSubresourceWithFieldsFilter(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableShippingMethods'
            ],
            ['fields[checkoutavailableshippingmethods]' => 'label']
        );

        $expectedResponseData = $this->updateResponseContent([
            'data' => [
                [
                    'type' => 'checkoutavailableshippingmethods',
                    'id' => 'shipping_method',
                    'attributes' => [
                        'label' => 'Flat Rate'
                    ]
                ]
            ]
        ], $response, 'id', 'shipping_method');
        $this->assertResponseContains($expectedResponseData, $response);
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContent['data'][0]['attributes']);
    }

    public function testCheckoutGetSubresourceForNotExistingCheckout(): void
    {
        $response = $this->getSubresource([
            'entity' => 'checkouts',
            'id' => '999999',
            'association' => 'availableShippingMethods'
        ]);

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testCheckoutGetSubresourceForDeletedCheckout(): void
    {
        $response = $this->getSubresource([
            'entity' => 'checkouts',
            'id' => '<toString(@checkout.deleted->id)>',
            'association' => 'availableShippingMethods'
        ]);

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToCheckoutGetRelationship(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableShippingMethods'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCheckoutUpdateRelationship(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableShippingMethods'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCheckoutAddRelationship(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableShippingMethods'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCheckoutDeleteRelationship(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'association' => 'availableShippingMethods'
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutavailableshippingmethods'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutavailableshippingmethods', 'id' => 'shipping_method'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutavailableshippingmethods'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'checkoutavailableshippingmethods', 'id' => 'shipping_method'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutavailableshippingmethods', 'id' => 'shipping_method'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'checkoutavailableshippingmethods'],
            ['filter' => ['checkout' => '@checkout.open->id']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
