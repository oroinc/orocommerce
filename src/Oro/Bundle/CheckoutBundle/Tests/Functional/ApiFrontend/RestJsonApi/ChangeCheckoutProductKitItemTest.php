<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChangeCheckoutProductKitItemTest extends FrontendRestJsonApiTestCase
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

    public function testCreateWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_checkout_kit_item_line_item_min.yml');
        $response = $this->post(
            ['entity' => 'checkoutproductkititemlineitems'],
            array_merge(['filters' => 'include=lineItem.checkout&fields[checkouts]=totalValue,totals'], $data)
        );
        $expectedData = $data;
        $expectedData['included'] = [
            [
                'type' => 'checkouts',
                'id' => '<toString(@checkout.in_progress->id)>',
                'attributes' => [
                    'totalValue' => '300.1200',
                    'totals' => [
                        ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '344.5800'],
                        ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-44.4600']
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdate(): void
    {
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemId,
                'attributes' => [
                    'quantity' => 2
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
            array_merge(['filters' => 'include=lineItem.checkout&fields[checkouts]=totalValue,totals'], $data)
        );

        /** @var CheckoutProductKitItemLineItem $kitItem */
        $kitItem = $this->getEntityManager()->find(CheckoutProductKitItemLineItem::class, $kitItemId);
        $expectedData = $data;
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.in_progress->id)>',
            'attributes' => [
                'totalValue' => '393.1000',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '447.8900'],
                    ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-54.7900']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(2.0, $kitItem->getQuantity());
    }

    public function testUpdateFromAnotherCustomerUser(): void
    {
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemId,
                'attributes' => [
                    'quantity' => 2
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
            array_merge(['filters' => 'include=lineItem.checkout&fields[checkouts]=totalValue,totals'], $data)
        );

        /** @var CheckoutProductKitItemLineItem $kitItem */
        $kitItem = $this->getEntityManager()->find(CheckoutProductKitItemLineItem::class, $kitItemId);
        $expectedData = $data;
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.in_progress->id)>',
            'attributes' => [
                'totalValue' => '393.1000',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '447.8900'],
                    ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-54.7900']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(2.0, $kitItem->getQuantity());
    }

    public function testTryToUpdateFromAnotherDepartment(): void
    {
        $kitItemId = $this->getReference('checkout.another_department_customer_user.line_item.1.kit_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId],
            [
                'data' => [
                    'type' => 'checkoutproductkititemlineitems',
                    'id' => (string)$kitItemId,
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

    public function testTryToUpdateRelationshipForLineItem(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'lineItem'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipForKitItem(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'kitItem'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipForProduct(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'product'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateRelationshipForProductUnit(): void
    {
        $response = $this->patchRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>',
                'association' => 'productUnit'
            ],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testDelete(): void
    {
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $this->delete(['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId]);
        $kitItem = $this->getEntityManager()->find(CheckoutProductKitItemLineItem::class, $kitItemId);
        self::assertTrue(null === $kitItem);
    }

    public function testDeleteFromAnotherCustomerUser(): void
    {
        $kitItemId = $this->getReference('checkout.another_customer_user.line_item.2.kit_item.1')->getId();
        $this->delete(['entity' => 'checkoutproductkititemlineitems', 'id' => (string)$kitItemId]);
        $kitItem = $this->getEntityManager()->find(CheckoutProductKitItemLineItem::class, $kitItemId);
        self::assertTrue(null === $kitItem);
    }

    public function testTryToDeleteFromAnotherDepartment(): void
    {
        $response = $this->delete(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.another_department_customer_user.line_item.1.kit_item.1->id)>'
            ],
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

    public function testDeleteList(): void
    {
        $kitItemId = $this->getReference('checkout.in_progress.line_item.2.kit_item.1')->getId();
        $this->cdelete(
            ['entity' => 'checkoutproductkititemlineitems'],
            ['filter' => ['id' => (string)$kitItemId]]
        );
        $kitItem = $this->getEntityManager()->find(CheckoutProductKitItemLineItem::class, $kitItemId);
        self::assertTrue(null === $kitItem);
    }
}
