<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChangeCheckoutLineItemTest extends FrontendRestJsonApiTestCase
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

    private function generateLineItemChecksum(CheckoutLineItem $lineItem): string
    {
        /** @var LineItemChecksumGeneratorInterface $lineItemChecksumGenerator */
        $lineItemChecksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');
        $checksum = $lineItemChecksumGenerator->getChecksum($lineItem);
        self::assertNotEmpty($checksum, 'Impossible to generate the line item checksum.');

        return $checksum;
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_min.yml');
        $response = $this->post(
            ['entity' => 'checkoutlineitems'],
            array_merge(['filters' => 'include=checkout&fields[checkouts]=totalValue,totals'], $data)
        );

        $lineItemId = $this->getResourceId($response);
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        $expectedChecksum = $this->generateLineItemChecksum($lineItem);
        $expectedData = $data;
        $expectedData['data']['id'] = $lineItemId;
        $expectedData['data']['attributes']['priceFixed'] = false;
        $expectedData['data']['attributes']['price'] = '100.5000';
        $expectedData['data']['attributes']['currency'] = 'USD';
        $expectedData['data']['attributes']['shippingMethod'] = null;
        $expectedData['data']['attributes']['shippingMethodType'] = null;
        $expectedData['data']['attributes']['shippingEstimateAmount'] = null;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $expectedData['data']['relationships']['group']['data'] = null;
        $expectedData['included'] = [
            [
                'type' => 'checkouts',
                'id' => '<toString(@checkout.open->id)>',
                'attributes' => [
                    'totalValue' => '121.4000',
                    'totals' => [
                        ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '121.4000']
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $lineItem->getChecksum());
    }

    public function testCreateWithReadonlySubTotal(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_min.yml');
        $data['data']['attributes']['subTotal'] = '123.123';
        $response = $this->post(['entity' => 'checkoutlineitems'], $data);

        $lineItemId = $this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['id'] = $lineItemId;
        $expectedData['data']['attributes']['subTotal'] = '100.5000';
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithReadonlyChecksum(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_min.yml');
        $data['data']['attributes']['checksum'] = '123456789';
        $response = $this->post(['entity' => 'checkoutlineitems'], $data);

        $lineItemId = $this->getResourceId($response);
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        $expectedChecksum = $this->generateLineItemChecksum($lineItem);
        $expectedData = $data;
        $expectedData['data']['id'] = $lineItemId;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $lineItem->getChecksum());
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
            array_merge(['filters' => 'include=checkout&fields[checkouts]=totalValue,totals'], $data)
        );

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        $expectedChecksum = $this->generateLineItemChecksum($lineItem);
        $expectedData = $data;
        $expectedData['data']['attributes']['price'] = '100.5000';
        $expectedData['data']['attributes']['currency'] = 'USD';
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.in_progress->id)>',
            'attributes' => [
                'totalValue' => '733.9900',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '733.9900']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(5.0, $lineItem->getQuantity());
        self::assertEquals($expectedChecksum, $lineItem->getChecksum());
    }

    public function testUpdateFromAnotherCustomerUser(): void
    {
        $lineItemId = $this->getReference('checkout.another_customer_user.line_item.1')->getId();
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
            array_merge(['filters' => 'include=checkout&fields[checkouts]=totalValue,totals'], $data)
        );

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        $expectedChecksum = $this->generateLineItemChecksum($lineItem);
        $expectedData = $data;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.another_customer_user->id)>',
            'attributes' => [
                'totalValue' => '633.4900',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '633.4900']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(5.0, $lineItem->getQuantity());
        self::assertEquals($expectedChecksum, $lineItem->getChecksum());
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

    public function testTryToUpdateReadonlySubTotal(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();

        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'subTotal' => '123.123'
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
                    'attributes' => [
                        'subTotal' => '1005.0000'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateReadonlyChecksum(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();

        $data = [
            'data' => [
                'type' => 'checkoutlineitems',
                'id' => (string)$lineItemId,
                'attributes' => [
                    'checksum' => '123456789'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            $data
        );

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        $expectedChecksum = $this->generateLineItemChecksum($lineItem);
        $expectedData = $data;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $lineItem->getChecksum());
    }

    public function testUpdatePrice(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'checkoutlineitems',
                'id' => (string)$lineItemId,
                'attributes' => [
                    'price' => 150.1,
                    'currency' => 'USD',
                    'priceFixed' => true
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            array_merge(['filters' => 'include=checkout&fields[checkouts]=totalValue,totals'], $data)
        );
        $expectedData = $data;
        $expectedData['data']['attributes']['price'] = '100.5000';
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.in_progress->id)>',
            'attributes' => [
                'totalValue' => '231.4900',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '231.4900']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertNull($lineItem->getValue());
        self::assertNull($lineItem->getCurrency());
    }

    public function testDelete(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $this->delete(['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId]);
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
    }

    public function testDeleteFromAnotherCustomerUser(): void
    {
        $lineItemId = $this->getReference('checkout.another_customer_user.line_item.1')->getId();
        $this->delete(['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId]);
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
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

    public function testDeleteList(): void
    {
        $lineItemId = $this->getReference('checkout.in_progress.line_item.1')->getId();
        $this->cdelete(
            ['entity' => 'checkoutlineitems'],
            ['filter' => ['id' => (string)$lineItemId]]
        );
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
    }
}
