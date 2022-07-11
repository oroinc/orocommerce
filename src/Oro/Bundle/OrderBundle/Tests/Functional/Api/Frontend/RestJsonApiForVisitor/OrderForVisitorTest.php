<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApiForVisitor;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrderForVisitorTest extends FrontendRestJsonApiTestCase
{
    private const COMMON_REQUESTS_PATH = '@OroOrderBundle/Tests/Functional/Api/Frontend/RestJsonApi/requests/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'
        ]);
    }

    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'orders'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGet()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'orders'],
            self::COMMON_REQUESTS_PATH . 'create_order.yml',
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'orders'],
            ['filter' => ['id' => '<toString(@order1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
    }

    public function testTryToGetSubresourceForLineItems()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForLineItems()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForLineItems()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForLineItems()
    {
        $response = $this->postRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForLineItems()
    {
        $response = $this->deleteRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'lineItems'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForCustomer()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customer'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForCustomerUser()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForCustomerUser()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForCustomerUser()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'customerUser'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForBillingAddress()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForBillingAddress()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForBillingAddress()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'billingAddress'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetSubresourceForShippingAddress()
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForShippingAddress()
    {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForShippingAddress()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>', 'association' => 'shippingAddress'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
