<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForUnauthenticated\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderForUnauthenticatedTest extends FrontendRestJsonApiTestCase
{
    public function testOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'orders']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'orders', 'id' => '1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'orders'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGet()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'orders'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'orders', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'orders', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'orders'],
            ['filter' => ['id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetSubresourceForLineItems()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '1', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetRelationshipForLineItems()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdateRelationshipForLineItems()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToAddRelationshipForLineItems()
    {
        $response = $this->postRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteRelationshipForLineItems()
    {
        $response = $this->deleteRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetSubresourceForCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '1', 'association' => 'customer'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetRelationshipForCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'customer'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdateRelationshipForCustomer()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'customer'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetSubresourceForCustomerUser()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '1', 'association' => 'customerUser'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetRelationshipForCustomerUser()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'customerUser'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdateRelationshipForCustomerUser()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'customerUser'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetSubresourceForBillingAddress()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '1', 'association' => 'billingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetRelationshipForBillingAddress()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'billingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdateRelationshipForBillingAddress()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'billingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetSubresourceForShippingAddress()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '1', 'association' => 'shippingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetRelationshipForShippingAddress()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'shippingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdateRelationshipForShippingAddress()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '1', 'association' => 'shippingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
}
