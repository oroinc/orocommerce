<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendShipping\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutLineItemGroupTest extends FrontendRestJsonApiTestCase
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

    private function getGroupId(string $productReference): string
    {
        return base64_encode(\sprintf(
            '%d-product.id:%d',
            $this->getCheckout()->getId(),
            $this->getReference($productReference)->getId()
        ));
    }

    public function testOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'checkoutlineitemgroups'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'checkoutlineitemgroups', 'id' => $this->getGroupId(LoadProductData::PRODUCT_2)]
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH');
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutlineitemgroups'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGet(): void
    {
        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2);
        $response = $this->get(
            ['entity' => 'checkoutlineitemgroups', 'id' => $groupId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitemgroups',
                    'id' => $groupId,
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
            ],
            $response
        );
    }

    public function testTryToGetForNotExistingGroup(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitemgroups', 'id' => $this->getGroupId(LoadProductData::PRODUCT_1)],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetForInvalidGroupIdInvalidEncoding(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitemgroups', 'id' => '#$%'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetForInvalidGroupIdNoLineItemGroupKey(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutlineitemgroups', 'id' => base64_encode('1')],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetForInvalidGroupIdEmptyLineItemGroupKey(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutlineitemgroups',
                'id' => base64_encode(\sprintf('%d-', $this->getReference('checkout.open')->getId()))
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetForInvalidGroupIdNotIntegerCheckoutId(): void
    {
        $response = $this->get(
            [
                'entity' => 'checkoutlineitemgroups',
                'id' => base64_encode(\sprintf(
                    'test-product.id:%d',
                    $this->getReference(LoadProductData::PRODUCT_2)->getId()
                ))
            ],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutlineitemgroups'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryUpdateWithInvalidShippingMethod(): void
    {
        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2);
        $response = $this->patch(
            ['entity' => 'checkoutlineitemgroups', 'id' => $groupId],
            [
                'data' => [
                    'type' => 'checkoutlineitemgroups',
                    'id' => $groupId,
                    'attributes' => [
                        'shippingMethod' => 'invalid_method',
                        'shippingMethodType' => 'invalid_type'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'shipping method is valid constraint',
                'detail' => 'Shipping method is not valid.'
            ],
            $response
        );
    }

    public function testTryUpdateWithInvalidShippingMethodType(): void
    {
        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2);
        $response = $this->patch(
            ['entity' => 'checkoutlineitemgroups', 'id' => $groupId],
            [
                'data' => [
                    'type' => 'checkoutlineitemgroups',
                    'id' => $groupId,
                    'attributes' => [
                        'shippingMethod' => '@checkout.completed->shippingMethod',
                        'shippingMethodType' => 'invalid_type'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'shipping method is valid constraint',
                'detail' => 'Shipping method type is not valid.'
            ],
            $response
        );
    }

    public function testUpdateEmpty(): void
    {
        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2);
        $response = $this->patch(
            ['entity' => 'checkoutlineitemgroups', 'id' => $groupId],
            [
                'data' => [
                    'type' => 'checkoutlineitemgroups',
                    'id' => $groupId
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitemgroups',
                    'id' => $groupId,
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
            ],
            $response
        );
        self::assertEquals([], $this->getCheckout()->getLineItemGroupShippingData());
    }

    public function testUpdate(): void
    {
        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2);
        $shippingMethod = $this->getCheckout('checkout.completed')->getShippingMethod();
        $shippingMethodType = $this->getCheckout('checkout.completed')->getShippingMethodType();
        $response = $this->patch(
            ['entity' => 'checkoutlineitemgroups', 'id' => $groupId],
            [
                'data' => [
                    'type' => 'checkoutlineitemgroups',
                    'id' => $groupId,
                    'attributes' => [
                        'shippingMethod' => $shippingMethod,
                        'shippingMethodType' => $shippingMethodType
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
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

    public function testUpdateReset(): void
    {
        $this->getCheckout()->setLineItemGroupShippingData([
            \sprintf('product.id:%d', $this->getReference(LoadProductData::PRODUCT_2)->getId()) => [
                'method' => $this->getCheckout('checkout.completed')->getShippingMethod(),
                'type' => $this->getCheckout('checkout.completed')->getShippingMethodType(),
                'amount' => 10
            ]
        ]);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $groupId = $this->getGroupId(LoadProductData::PRODUCT_2);
        $response = $this->patch(
            ['entity' => 'checkoutlineitemgroups', 'id' => $groupId],
            [
                'data' => [
                    'type' => 'checkoutlineitemgroups',
                    'id' => $groupId,
                    'attributes' => [
                        'shippingMethod' => null,
                        'shippingMethodType' => null
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'checkoutlineitemgroups',
                    'id' => $groupId,
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
            ],
            $response
        );
        self::assertEquals([], $this->getCheckout()->getLineItemGroupShippingData());
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutlineitemgroups', 'id' => $this->getGroupId(LoadProductData::PRODUCT_2)],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'checkoutlineitemgroups'],
            ['filter[id]' => $this->getGroupId(LoadProductData::PRODUCT_2)],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
