<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutLineItemTest extends FrontendRestJsonApiTestCase
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
        $response = $this->cget(['entity' => 'checkoutlineitems']);
        $this->assertResponseContains('cget_checkout_line_item.yml', $response);
    }

    public function testGetListFilteredByCheckout(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutlineitems'],
            ['filter[checkout]' => '<toString(@checkout.in_progress->id)>']
        );
        $this->assertResponseContains('cget_checkout_line_item_filter_by_checkout.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>']
        );
        $this->assertResponseContains('get_checkout_line_item.yml', $response);
    }

    public function testTryToGetForDeletedCheckout(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.deleted.line_item.2->id)>'],
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
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.another_customer_user.line_item.1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => '<toString(@checkout.another_customer_user.line_item.1->id)>'
                ]
            ],
            $response
        );
    }

    public function testTryToGetFromAnotherDepartment(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.another_department_customer_user.line_item.1->id)>'
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

    public function testGetWithProductKitLineItem(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.2->id)>']
        );
        $this->assertResponseContains('get_checkout_line_item_with_kit_item.yml', $response);
    }

    public function testGetSubresourceForCheckout(): void
    {
        $response = $this->getSubresource(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'checkout'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCheckout(): void
    {
        $response = $this->getRelationship(
            [
                'entity' => 'checkoutlineitems',
                'id' => '<toString(@checkout.in_progress.line_item.1->id)>',
                'association' => 'checkout'
            ]
        );
        $this->assertResponseContains(
            ['data' => ['type' => 'checkouts', 'id' => '<toString(@checkout.in_progress->id)>']],
            $response
        );
    }

    public function testGetSubresourceForKitItemLineItems(): void
    {
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getReference('checkout.in_progress.line_item.1');
        $kitItemLineItemsData = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                'type' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId()
            ];
        }
        $response = $this->getSubresource(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItem->getId(), 'association' => 'kitItemLineItems']
        );
        $this->assertResponseContains(['data' => $kitItemLineItemsData], $response);
    }

    public function testGetRelationshipForKitItemLineItems(): void
    {
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getReference('checkout.in_progress.line_item.1');
        $kitItemLineItemsData = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                'type' => 'checkoutproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId()
            ];
        }
        $response = $this->getRelationship(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItem->getId(), 'association' => 'kitItemLineItems']
        );
        $this->assertResponseContains(['data' => $kitItemLineItemsData], $response);
    }
}
