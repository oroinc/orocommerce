<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendShipping\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodProvider;

/**
 * @dbIsolationPerTest
 */
class CheckoutShippingMethodPerLineItemTest extends FrontendRestJsonApiTestCase
{
    use ConfigManagerAwareTestTrait;

    private const string ENABLE_SHIPPING_PER_LINE_ITEM = 'oro_checkout.enable_shipping_method_selection_per_line_item';
    private const string ENABLE_LINE_ITEM_GROUPING = 'oro_checkout.enable_line_item_grouping';
    private const string GROUP_LINE_ITEMS_BY = 'oro_checkout.group_line_items_by';

    private ?bool $originalEnableShippingPerLineItem;
    private ?bool $originalEnableLineItemGrouping;
    private ?string $originalGroupLineItemsBy;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadCompetedCheckoutData::class
        ]);

        $configManager = self::getConfigManager();
        $this->originalEnableShippingPerLineItem = $configManager->get(self::ENABLE_SHIPPING_PER_LINE_ITEM);
        $this->originalEnableLineItemGrouping = $configManager->get(self::ENABLE_LINE_ITEM_GROUPING);
        $this->originalGroupLineItemsBy = $configManager->get(self::GROUP_LINE_ITEMS_BY);
        $configManager->set(self::ENABLE_SHIPPING_PER_LINE_ITEM, true);
        $configManager->set(self::ENABLE_LINE_ITEM_GROUPING, true);
        $configManager->set(self::GROUP_LINE_ITEMS_BY, 'product.id');
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(self::ENABLE_SHIPPING_PER_LINE_ITEM, $this->originalEnableShippingPerLineItem);
        $configManager->set(self::ENABLE_LINE_ITEM_GROUPING, $this->originalEnableLineItemGrouping);
        $configManager->set(self::GROUP_LINE_ITEMS_BY, $this->originalGroupLineItemsBy);
        $configManager->flush();
    }

    private function getCheckout(string $checkoutReference = 'checkout.open'): Checkout
    {
        return $this->getReference($checkoutReference);
    }

    private function getGroupId(string $productReference, ?Checkout $checkout = null): string
    {
        if (null === $checkout) {
            $checkout = $this->getCheckout();
        }

        return base64_encode(\sprintf(
            '%d-product.id:%d',
            $checkout->getId(),
            $this->getReference($productReference)->getId()
        ));
    }

    public function testGetCheckout(): void
    {
        $checkout = $this->getCheckout();
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => (string)$checkout->getId()]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkout->getId(),
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                [
                                    'type' => 'checkoutlineitems',
                                    'id' => '<toString(@checkout.open.line_item.1->id)>'
                                ]
                            ]
                        ],
                        'lineItemGroups' => [
                            'data' => [
                                [
                                    'type' => 'checkoutlineitemgroups',
                                    'id' => $this->getGroupId(LoadProductData::PRODUCT_2, $checkout)
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateShippingMethodForLineItems(): void
    {
        $checkout = $this->getCheckout();
        $lineItemId = $checkout->getLineItems()->first()->getId();
        $shippingMethod = $this->getCheckout('checkout.completed')->getShippingMethod();
        $shippingMethodType = $this->getCheckout('checkout.completed')->getShippingMethodType();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkout->getId()],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkout->getId(),
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                [
                                    'type' => 'checkoutlineitems',
                                    'id' => (string)$lineItemId
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'meta' => ['update' => true],
                        'type' => 'checkoutlineitems',
                        'id' => (string)$lineItemId,
                        'attributes' => [
                            'shippingMethod' => $shippingMethod,
                            'shippingMethodType' => $shippingMethodType
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkout->getId(),
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                [
                                    'type' => 'checkoutlineitems',
                                    'id' => (string)$lineItemId
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'meta' => ['includeId' => (string)$lineItemId],
                        'type' => 'checkoutlineitems',
                        'id' => (string)$lineItemId,
                        'attributes' => [
                            'shippingMethod' => $shippingMethod,
                            'shippingMethodType' => $shippingMethodType
                        ]
                    ]
                ]
            ],
            $response
        );
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(CheckoutLineItem::class, $lineItemId);
        self::assertEquals($shippingMethod, $lineItem->getShippingMethod());
        self::assertEquals($shippingMethodType, $lineItem->getShippingMethodType());
    }

    public function testTryToUpdateShippingMethodForLineItemsWhenMetaUpdateAttributeWasNotProvides(): void
    {
        $checkout = $this->getCheckout();
        $lineItemId = $checkout->getLineItems()->first()->getId();
        $shippingMethod = $this->getCheckout('checkout.completed')->getShippingMethod();
        $shippingMethodType = $this->getCheckout('checkout.completed')->getShippingMethodType();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkout->getId()],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkout->getId(),
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                [
                                    'type' => 'checkoutlineitems',
                                    'id' => (string)$lineItemId
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'checkoutlineitems',
                        'id' => (string)$lineItemId,
                        'attributes' => [
                            'shippingMethod' => $shippingMethod,
                            'shippingMethodType' => $shippingMethodType
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not blank constraint',
                    'detail' => 'The quantity should be greater than 0.',
                    'source' => ['pointer' => '/included/0/attributes/quantity']
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/included/0/relationships/product/data']
                ],
                [
                    'title' => 'not null constraint',
                    'detail' => 'This value should not be null.',
                    'source' => ['pointer' => '/included/0/relationships/productUnit/data']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateShippingMethodForCheckout(): void
    {
        $checkoutId = $this->getReference('checkout.open')->getId();
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'shippingMethod' => '@checkout.completed->shippingMethod',
                        'shippingMethodType' => '@checkout.completed->shippingMethodType'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'shipping method change constraint',
                    'detail' => 'This value can be changed only when the shipping type is "checkout".',
                    'source' => ['pointer' => '/data/attributes/shippingMethod']
                ],
                [
                    'title' => 'shipping method change constraint',
                    'detail' => 'This value can be changed only when the shipping type is "checkout".',
                    'source' => ['pointer' => '/data/attributes/shippingMethodType']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateShippingMethodForLineItemGroups(): void
    {
        $checkout = $this->getCheckout();
        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2, $checkout);
        $response = $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkout->getId()],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkout->getId(),
                    'relationships' => [
                        'lineItemGroups' => [
                            'data' => [
                                [
                                    'type' => 'checkoutlineitemgroups',
                                    'id' => $groupId
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'meta' => ['update' => true],
                        'type' => 'checkoutlineitemgroups',
                        'id' => $groupId,
                        'attributes' => [
                            'shippingMethod' => '@checkout.completed->shippingMethod',
                            'shippingMethodType' => '@checkout.completed->shippingMethodType'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'shipping method change constraint',
                    'detail' => 'This value can be changed only when the shipping type is "line_item_group".',
                    'source' => ['pointer' => '/included/0/attributes/shippingMethod']
                ],
                [
                    'title' => 'shipping method change constraint',
                    'detail' => 'This value can be changed only when the shipping type is "line_item_group".',
                    'source' => ['pointer' => '/included/0/attributes/shippingMethodType']
                ]
            ],
            $response
        );
    }

    public function testCreateCheckoutWhenDefaultShippingMethodShouldBeSet(): void
    {
        $data = $this->getRequestData('../../ApiFrontend/RestJsonApi/requests/create_checkout_with_full_data.yml');
        unset($data['data']['attributes']['shippingMethod'], $data['data']['attributes']['shippingMethodType']);
        $response = $this->post(
            ['entity' => 'checkouts'],
            $data
        );

        $checkout = $this->getEntityManager()->find(Checkout::class, (int)$this->getResourceId($response));
        self::assertNotNull($checkout);

        self::assertEquals(
            MultiShippingMethodProvider::MULTI_SHIPPING_METHOD_IDENTIFIER,
            $checkout->getShippingMethod()
        );
        self::assertEquals('primary', $checkout->getShippingMethodType());
        self::assertEquals([], $checkout->getLineItemGroupShippingData());
        foreach ($checkout->getLineItems() as $lineItem) {
            self::assertEquals(
                $this->getReference('checkout.completed')->getShippingMethod(),
                $lineItem->getShippingMethod()
            );
            self::assertEquals('primary', $lineItem->getShippingMethodType());
        }
    }

    public function testUpdateCheckoutWhenDefaultShippingMethodShouldBeSet(): void
    {
        $checkoutId = $this->getReference('checkout.empty')->getId();

        $data = $this->getRequestData('../../ApiFrontend/RestJsonApi/requests/update_checkout_with_full_data.yml');
        unset($data['data']['attributes']['shippingMethod'], $data['data']['attributes']['shippingMethodType']);
        $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            $data
        );

        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        self::assertEquals(
            MultiShippingMethodProvider::MULTI_SHIPPING_METHOD_IDENTIFIER,
            $checkout->getShippingMethod()
        );
        self::assertEquals('primary', $checkout->getShippingMethodType());
        self::assertEquals([], $checkout->getLineItemGroupShippingData());
        foreach ($checkout->getLineItems() as $lineItem) {
            self::assertEquals(
                $this->getReference('checkout.completed')->getShippingMethod(),
                $lineItem->getShippingMethod()
            );
            self::assertEquals('primary', $lineItem->getShippingMethodType());
        }
    }
}
