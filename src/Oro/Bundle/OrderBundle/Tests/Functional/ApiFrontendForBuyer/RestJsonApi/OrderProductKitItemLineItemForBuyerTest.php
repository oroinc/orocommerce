<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderProductKitItemLineItemForBuyerTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml'
        ]);
    }

    #[\Override]
    protected function getRequestDataFolderName(): string
    {
        return '../../ApiFrontend/RestJsonApi/requests';
    }

    #[\Override]
    protected function getResponseDataFolderName(): string
    {
        return '../../ApiFrontend/RestJsonApi/responses';
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderproductkititemlineitems']);

        $this->assertResponseContains('cget_order_product_kit_item_line_item_for_buyer.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order_product_kit_3_line_item.1_kit_item_line_item.1->id)>',
            ]
        );

        $this->assertResponseContains('get_order_product_kit_item_line_item.yml', $response);
    }

    public function testTryToGetForChildCustomer(): void
    {
        $response = $this->get(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order5_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetForCustomerFromAnotherDepartment(): void
    {
        $response = $this->get(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@another_order2_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'orderproductkititemlineitems'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'Use API resource to create an order.'
                    . ' An order product kit item line item can be created only together with an order.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
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
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'lineItem',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orderlineitems', 'id' => (string)$kitItemLineItem->getLineItem()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForLineItem(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'lineItem',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orderlineitems', 'id' => (string)$kitItemLineItem->getLineItem()->getId()]],
            $response
        );
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
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'kitItem',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productkititems', 'id' => (string)$kitItemLineItem->getKitItem()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForKitItem(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'kitItem',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productkititems', 'id' => (string)$kitItemLineItem->getKitItem()->getId()]],
            $response
        );
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
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'product',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$kitItemLineItem->getProduct()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForProduct(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'product',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$kitItemLineItem->getProduct()->getId()]],
            $response
        );
    }

    public function testUpdateRelationshipForProduct(): void
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
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'productUnit',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $kitItemLineItem->getProductUnit()->getCode()]],
            $response
        );
    }

    public function testGetRelationshipForUnit(): void
    {
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1');

        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'productUnit',
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $kitItemLineItem->getProductUnit()->getCode()]],
            $response
        );
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

    // CHECKED!
    public function testTryToGetSubresourceForLineItemForChildCustomer(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order5_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'lineItem',
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    // checked!
    public function testTryToGetRelationshipForLineItemForChildCustomer(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@order5_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'lineItem',
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    // checked!
    public function testTryToGetSubresourceForLineItemForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@another_order2_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'lineItem',
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    // checked!
    public function testTryToGetRelationshipForLineItemForCustomerFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'orderproductkititemlineitems',
                'id' => '<toString(@another_order2_product_kit_2_line_item.1_kit_item_line_item.1->id)>',
                'association' => 'lineItem',
            ],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the parent entity.',
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
