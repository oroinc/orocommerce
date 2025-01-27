<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForBuyer\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutLineItemForBuyerTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            LoadCheckoutData::class
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'checkoutlineitems']);
        $this->assertResponseContains('cget_checkout_line_item.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.in_progress.line_item.1->id)>']
        );
        $this->assertResponseContains(
            '@OroCheckoutBundle/Tests/Functional/ApiFrontend/RestJsonApi/responses/get_checkout_line_item.yml',
            $response
        );
    }

    public function testTryToGetFromAnotherCustomerUser(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.another_customer_user.line_item.1->id)>'],
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

    public function testCreate(): void
    {
        $data = $this->getRequestData('../../ApiFrontend/RestJsonApi/requests/create_checkout_line_item_min.yml');
        $response = $this->post(['entity' => 'checkoutlineitems'], $data);
        $this->assertResponseContains($data, $response);
    }

    public function testUpdate(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutlineitems',
                'id' => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 5
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            $data
        );
        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateFromAnotherCustomerUser(): void
    {
        $lineItemId = $this->getReference('checkout.another_customer_user.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 5
                    ]
                ]
            ],
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

    public function testTryToUpdateFromAnotherDepartment(): void
    {
        $lineItemId = $this->getReference('checkout.another_department_customer_user.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 5
                    ]
                ]
            ],
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

    public function testDelete(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $this->delete(['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId]);
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
    }

    public function testTryToDeleteFromAnotherCustomerUser(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutlineitems', 'id' => '<toString(@checkout.another_customer_user.line_item.1->id)>'],
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

    public function testTryToDeleteFromAnotherDepartment(): void
    {
        $response = $this->delete(
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
}
