<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
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

    private function assertCheckoutTotal(
        Checkout $checkout,
        float $total,
        string $currency
    ): void {
        /** @var ShoppingListTotal[] $totals */
        $totals = $this->getEntityManager()
            ->getRepository(CheckoutSubtotal::class)
            ->findBy(['checkout' => $checkout]);
        self::assertCount(1, $totals);
        $totalEntity = $totals[0];
        self::assertEquals($total, $totalEntity->getSubtotal()->getAmount());
        self::assertEquals($currency, $totalEntity->getCurrency());
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_checkout_line_item_min.yml');
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
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $expectedData['included'][] = [
            'type' => 'checkouts',
            'id' => '<toString(@checkout.in_progress->id)>',
            'attributes' => [
                'totalValue' => '650.5900',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '733.9900'],
                    ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-83.4000']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(5.0, $lineItem->getQuantity());
        $this->assertCheckoutTotal($lineItem->getCheckout(), 736.49, 'USD');
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
                'totalValue' => '623.4900',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '633.4900'],
                    ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-10.0000']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(5.0, $lineItem->getQuantity());
        $this->assertCheckoutTotal($lineItem->getCheckout(), 736.49, 'USD');
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
                'totalValue' => '288.7900',
                'totals' => [
                    ['subtotalType' => 'subtotal', 'description' => 'Subtotal', 'amount' => '331.9900'],
                    ['subtotalType' => 'discount', 'description' => 'Discount', 'amount' => '-43.2000']
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertEquals(100.5, $lineItem->getValue());
        self::assertEquals('USD', $lineItem->getCurrency());
        $this->assertCheckoutTotal($lineItem->getCheckout(), 334.49, 'USD');
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
