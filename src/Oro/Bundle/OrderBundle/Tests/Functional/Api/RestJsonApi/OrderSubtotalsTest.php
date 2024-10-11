<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderSubtotalsTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml']);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'ordersubtotals'],
            ['filter' => ['order' => '@simple_order->id']],
        );

        $this->assertResponseContains('get_order_subtotals.yml', $response);
    }

    public function testGetListWithFieldsFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'ordersubtotals'],
            [
                'filter' => ['order' => '@simple_order->id'],
                'fields[ordersubtotals]' => 'label,amount'
            ],
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'ordersubtotals',
                        'id' => '<(implode("-", [@simple_order->id, "subtotal", "0"]))>',
                        'attributes' => ['label' => 'Subtotal', 'amount' => '789.0000']
                    ],
                    [
                        'type' => 'ordersubtotals',
                        'id' => '<(implode("-", [@simple_order->id, "discount", "1"]))>',
                        'attributes' => ['label' => 'Discount', 'amount' => '0.0000']
                    ],
                    [
                        'type' => 'ordersubtotals',
                        'id' => '<(implode("-", [@simple_order->id, "shipping_cost", "2"]))>',
                        'attributes' => ['label' => 'Shipping', 'amount' => '0.0000']
                    ],
                    [
                        'type' => 'ordersubtotals',
                        'id' => '<(implode("-", [@simple_order->id, "discount", "3"]))>',
                        'attributes' => ['label' => 'Shipping Discount', 'amount' => '0.0000']
                    ],
                    [
                        'type' => 'ordersubtotals',
                        'id' => '<(implode("-", [@simple_order->id, "tax", "4"]))>',
                        'attributes' => ['label' => 'Subtotal Tax', 'amount' => '0.0000']
                    ],
                    [
                        'type' => 'ordersubtotals',
                        'id' => '<(implode("-", [@simple_order->id, "tax", "5"]))>',
                        'attributes' => ['label' => 'Shipping Tax', 'amount' => '0.0000']
                    ],
                    [
                        'type' => 'ordersubtotals',
                        'id' => '<(implode("-", [@simple_order->id, "tax", "6"]))>',
                        'attributes' => ['label' => 'Tax', 'amount' => '0.0000']
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('currency', $responseContent['data'][0]['attributes']);
        self::assertArrayNotHasKey('relationships', $responseContent['data'][0]);
    }

    public function testGetListWithIncludeFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'ordersubtotals'],
            [
                'filter' => ['order' => '@simple_order->id'],
                'include' => 'order,priceList'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "subtotal", "0"]))>'],
                    ['type' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "discount", "1"]))>'],
                    ['type' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "shipping_cost", "2"]))>'],
                    ['type' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "discount", "3"]))>'],
                    ['type' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "tax", "4"]))>'],
                    ['type' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "tax", "5"]))>'],
                    ['type' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "tax", "6"]))>']
                ],
                'included' => [
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order->id)>',
                        'attributes' => ['poNumber' => '1234567890']
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetListWithoutRequiredFilters(): void
    {
        $response = $this->cget(['entity' => 'ordersubtotals'], [], [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'filter constraint',
                    'detail' => 'The "order" filter is required.',
                ],
            ],
            $response
        );
    }

    public function testTryToGetListWithInvalidOrderFilterValue(): void
    {
        $response = $this->cget(
            ['entity' => 'ordersubtotals'],
            ['filter' => ['order' => 'text']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Expected integer value. Given "text".',
                'source' => ['parameter' => 'filter[order]']
            ],
            $response
        );
    }

    public function testGetListForNotExistingOrder(): void
    {
        $response = $this->cget(
            ['entity' => 'ordersubtotals'],
            ['filter' => ['order' => 999999]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "subtotal", "0"]))>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'ordersubtotals'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "subtotal", "0"]))>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'ordersubtotals', 'id' => '<(implode("-", [@simple_order->id, "subtotal", "0"]))>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'ordersubtotals'],
            ['filter' => ['order' => '@simple_order->id']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetListForNotAccessibleCustomer(): void
    {
        $this->updateRolePermission('ROLE_ADMINISTRATOR', Order::class, AccessLevel::NONE_LEVEL);

        $response = $this->cget(
            ['entity' => 'ordersubtotals'],
            ['filter' => ['order' => '@simple_order->id']],
        );

        $this->assertResponseContains(['data' => []], $response);
    }
}
