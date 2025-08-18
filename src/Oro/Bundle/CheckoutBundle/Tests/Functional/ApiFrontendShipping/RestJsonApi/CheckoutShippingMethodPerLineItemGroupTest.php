<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendShipping\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodProvider;

/**
 * @dbIsolationPerTest
 */
class CheckoutShippingMethodPerLineItemGroupTest extends FrontendRestJsonApiTestCase
{
    private const string ENABLE_LINE_ITEM_GROUPING = 'oro_checkout.enable_line_item_grouping';
    private const string GROUP_LINE_ITEMS_BY = 'oro_checkout.group_line_items_by';

    private ?bool $initialEnableLineItemGrouping;
    private ?string $initialGroupLineItemsBy;

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
        $this->initialEnableLineItemGrouping = $configManager->get(self::ENABLE_LINE_ITEM_GROUPING);
        $this->initialGroupLineItemsBy = $configManager->get(self::GROUP_LINE_ITEMS_BY);
        $configManager->set(self::ENABLE_LINE_ITEM_GROUPING, true);
        $configManager->set(self::GROUP_LINE_ITEMS_BY, 'product.id');
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(self::ENABLE_LINE_ITEM_GROUPING, $this->initialEnableLineItemGrouping);
        $configManager->set(self::GROUP_LINE_ITEMS_BY, $this->initialGroupLineItemsBy);
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

    public function testGetCheckoutWithExpandedLineItemGroups(): void
    {
        $checkout = $this->getCheckout();
        $response = $this->get(
            ['entity' => 'checkouts', 'id' => (string)$checkout->getId()],
            ['include' => 'lineItemGroups']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkout->getId(),
                    'relationships' => [
                        'lineItemGroups' => [
                            'data' => [
                                [
                                    'type' => 'checkoutlineitemgroups',
                                    'id' => $this->getGroupId(LoadProductData::PRODUCT_2, $checkout)
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'checkoutlineitemgroups',
                        'id' => $this->getGroupId(LoadProductData::PRODUCT_2, $checkout),
                        'attributes' => [
                            'name' => 'product-2',
                            'itemCount' => 1,
                            'totalValue' => '20.9000',
                            'currency' => 'USD',
                            'shippingMethod' => null,
                            'shippingMethodType' => null,
                            'shippingEstimateAmount' => null
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateShippingMethodForLineItemGroups(): void
    {
        $checkout = $this->getCheckout();
        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2, $checkout);
        $shippingMethod = $this->getCheckout('checkout.completed')->getShippingMethod();
        $shippingMethodType = $this->getCheckout('checkout.completed')->getShippingMethodType();
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
                        'meta' => ['includeId' => $groupId],
                        'type' => 'checkoutlineitemgroups',
                        'id' => $groupId,
                        'attributes' => [
                            'name' => 'product-2',
                            'itemCount' => 1,
                            'totalValue' => '20.9000',
                            'currency' => 'USD',
                            'shippingMethod' => $shippingMethod,
                            'shippingMethodType' => $shippingMethodType,
                            'shippingEstimateAmount' => '10.0000'
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertEquals(
            [
                \sprintf('product.id:%d', $this->getReference(LoadProductData::PRODUCT_2)->getId()) => [
                    'method' => $shippingMethod,
                    'type' => $shippingMethodType,
                    'amount' => 10
                ]
            ],
            $this->getCheckout()->getLineItemGroupShippingData()
        );
    }

    public function testTryToUpdateShippingMethodForLineItemGroupsWhenMetaUpdateAttributeWasNotProvides(): void
    {
        $checkout = $this->getCheckout();
        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2, $checkout);
        $shippingMethod = $this->getCheckout('checkout.completed')->getShippingMethod();
        $shippingMethodType = $this->getCheckout('checkout.completed')->getShippingMethodType();
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
                        'type' => 'checkoutlineitemgroups',
                        'id' => $groupId,
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
        $this->assertResponseValidationError(
            [
                'title' => 'action not allowed exception',
                'detail' => 'The "create" action is not allowed.',
                'source' => ['pointer' => '/included/0']
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

    public function testTryToUpdateShippingMethodForLineItem(): void
    {
        $lineItemId = $this->getReference('checkout.open.line_item.1')->getId();
        $response = $this->patch(
            ['entity' => 'checkoutlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type' => 'checkoutlineitems',
                    'id' => (string)$lineItemId,
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
                    'detail' => 'This value can be changed only when the shipping type is "line_item".',
                    'source' => ['pointer' => '/data/attributes/shippingMethod']
                ],
                [
                    'title' => 'shipping method change constraint',
                    'detail' => 'This value can be changed only when the shipping type is "line_item".',
                    'source' => ['pointer' => '/data/attributes/shippingMethodType']
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
        self::assertEquals(
            [
                'product.id:' . $this->getReference('product-1')->getId() => [
                    'method' => $this->getReference('checkout.completed')->getShippingMethod(),
                    'type' => 'primary',
                    'amount' => 10
                ]
            ],
            $checkout->getLineItemGroupShippingData()
        );
        foreach ($checkout->getLineItems() as $lineItem) {
            self::assertEquals(null, $lineItem->getShippingMethod());
            self::assertEquals(null, $lineItem->getShippingMethodType());
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
        self::assertEquals(
            [
                'product.id:' . $this->getReference('product-1')->getId() => [
                    'method' => $this->getReference('checkout.completed')->getShippingMethod(),
                    'type' => 'primary',
                    'amount' => 10
                ]
            ],
            $checkout->getLineItemGroupShippingData()
        );
        foreach ($checkout->getLineItems() as $lineItem) {
            self::assertEquals(null, $lineItem->getShippingMethod());
            self::assertEquals(null, $lineItem->getShippingMethodType());
        }
    }
}
