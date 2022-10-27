<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderLineItemTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orderlineitems']);

        $this->assertResponseContains('cget_line_item.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>']
        );

        $this->assertResponseContains('get_line_item.yml', $response);
    }

    public function testGetForChildCustomer()
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>']],
            $response
        );
    }

    public function testTryToGetForCustomerFromAnotherDepartment()
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@another_order_line_item1->id)>'],
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

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'orderlineitems'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'Use API resource to create an order.'
                    . ' An order line item can be created only together with an order.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
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

    public function testGetSubresourceForOrder()
    {
        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'order']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order1->id)>']],
            $response
        );
    }

    public function testGetRelationshipForOrder()
    {
        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order1_line_item1->id)>', 'association' => 'order']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order1->id)>']],
            $response
        );
    }

    public function testGetSubresourceForOrderForChildCustomer()
    {
        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>', 'association' => 'order']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order3->id)>']],
            $response
        );
    }

    public function testGetRelationshipForOrderForChildCustomer()
    {
        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order3_line_item1->id)>', 'association' => 'order']
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => '<toString(@order3->id)>']],
            $response
        );
    }

    public function testTryToGetSubresourceForOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'orderlineitems',
                'id'          => '<toString(@another_order_line_item1->id)>',
                'association' => 'order'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetRelationshipForOrderForCustomerFromAnotherDepartment()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'orderlineitems',
                'id'          => '<toString(@another_order_line_item1->id)>',
                'association' => 'order'
            ],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
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
}
