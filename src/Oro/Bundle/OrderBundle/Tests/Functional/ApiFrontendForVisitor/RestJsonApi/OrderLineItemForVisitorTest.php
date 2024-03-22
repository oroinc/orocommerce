<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderLineItemForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            LoadCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGet()
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'orderlineitems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'orderlineitems'],
            ['filter' => ['id' => '<toString(@order1_line_item1->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
    }

    public function testTryToGetSubresourceForOrder()
    {
        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'order'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRelationshipForOrder()
    {
        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'order'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForOrder()
    {
        $response = $this->patchRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'order'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForKitItemLineItems(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetRelationshipForKitItemLineItems(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForKitItemLineItems(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
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
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
            ],
            [
                'data' => [],
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForKitItemLineItems(): void
    {
        $response = $this->deleteRelationship(
            [
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
            ],
            [
                'data' => [
                    [
                        'type' => 'orderproductkititemlineitems',
                        'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                    ],
                ],
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
