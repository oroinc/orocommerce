<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutProductKitItemTest extends FrontendRestJsonApiTestCase
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

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutproductkititemlineitems']);
        $this->assertResponseContains('cget_checkout_kit_item_line_item.yml', $response);
    }

    public function testGetListFilteredByLineItem(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutproductkititemlineitems'],
            ['filter[lineItem]' => '<toString(@checkout.in_progress.line_item.2->id)>']
        );
        $this->assertResponseContains('cget_checkout_kit_item_line_item_filter_by_line_item.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.2.kit_item.1->id)>'
            ]
        );
        $this->assertResponseContains('get_checkout_kit_item_line_item.yml', $response);
    }

    public function testTryToGetForDeletedCheckout(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.deleted.line_item.1.kit_item.1->id)>'
            ],
            [],
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

    public function testGetFromAnotherCustomerUser(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.another_customer_user.line_item.2.kit_item.1->id)>'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutproductkititemlineitems',
                    'id' => '<toString(@checkout.another_customer_user.line_item.2.kit_item.1->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetFromAnotherDepartment(): void
    {
        $response = $this->get(
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
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testGetSubresourceForLineItem(): void
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('checkout.in_progress.line_item.2.kit_item.1');
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'lineItem'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'checkoutlineitems', 'id' => (string)$kitItemLineItem->getLineItem()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForLineItem(): void
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('checkout.in_progress.line_item.2.kit_item.1');
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'lineItem'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'checkoutlineitems', 'id' => (string)$kitItemLineItem->getLineItem()->getId()]],
            $response
        );
    }

    public function testGetSubresourceForKitItem(): void
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('checkout.in_progress.line_item.2.kit_item.1');
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'kitItem'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'productkititems', 'id' => (string)$kitItemLineItem->getKitItem()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForKitItem(): void
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('checkout.in_progress.line_item.2.kit_item.1');
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'kitItem'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'productkititems', 'id' => (string)$kitItemLineItem->getKitItem()->getId()]],
            $response
        );
    }

    public function testGetSubresourceForProduct(): void
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('checkout.in_progress.line_item.2.kit_item.1');
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'product'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$kitItemLineItem->getProduct()->getId()]],
            $response
        );
    }

    public function testGetRelationshipForProduct(): void
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('checkout.in_progress.line_item.2.kit_item.1');
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'product'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$kitItemLineItem->getProduct()->getId()]],
            $response
        );
    }

    public function testGetSubresourceForProductUnit(): void
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('checkout.in_progress.line_item.2.kit_item.1');
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'productUnit'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $kitItemLineItem->getProductUnit()->getCode()]],
            $response
        );
    }

    public function testGetRelationshipForProductUnit(): void
    {
        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $this->getReference('checkout.in_progress.line_item.2.kit_item.1');
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
                'association' => 'productUnit'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $kitItemLineItem->getProductUnit()->getCode()]],
            $response
        );
    }

    public function testTryToGetSubresourceForLineItemFromAnotherDepartment(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.another_department_customer_user.line_item.1.kit_item.1->id)>',
                'association' => 'lineItem'
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

    public function testTryToGetRelationshipForLineItemFromAnotherDepartment(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutproductkititemlineitems',
                'id' => '<toString(@checkout.another_department_customer_user.line_item.1.kit_item.1->id)>',
                'association' => 'lineItem'
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
}
