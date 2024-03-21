<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderProductKitItemLineItemForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'orderproductkititemlineitems'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_3_line_item.1_kit_item_line_item.1->id)>',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'orderproductkititemlineitems'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
            ],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
            ],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'orderproductkititemlineitems'],
            ['filter' => ['id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
    }

    public function testGetSubresourceForLineItem(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'lineItem',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetRelationshipForLineItem(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'lineItem',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForLineItem(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'lineItem',
            ],
            ['data' => ['type' => 'orderlineitems', 'id' => '<toString(@product_kit_3_line_item.1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForKitItem(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'kitItem',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetRelationshipForKitItem(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'kitItem',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForKitItem(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'kitItem',
            ],
            ['data' => ['type' => 'productkititems', 'id' => '<toString(@product_kit_3_line_item.1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProduct(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'product',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetRelationshipForProduct(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'product',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForProduct(): void
    {
        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();
        $productId = $this->getReference('product-2')->getId();

        $response = $this->patchRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItemId,
                'association' => 'product',
            ],
            ['data' => ['type' => 'products', 'id' => (string)$productId]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForUnit(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'productUnit',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetRelationshipForUnit(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'productUnit',
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateRelationshipForUnit(): void
    {
        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();
        $productUnitCode = $this->getReference('product_unit.liter')->getCode();

        $response = $this->patchRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItemId,
                'association' => 'productUnit',
            ],
            ['data' => ['type' => 'productunits', 'id' => (string)$productUnitCode]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
