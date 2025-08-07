<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

class CheckoutSplitOrderCreationTest extends FrontendRestJsonApiTestCase
{
    private const string ENABLE_LINE_ITEM_GROUPING = 'oro_checkout.enable_line_item_grouping';
    private const string GROUP_LINE_ITEMS_BY = 'oro_checkout.group_line_items_by';
    private const string ENABLE_SPLIT_ORDERS = 'oro_checkout.create_suborders_for_each_group';

    private ?bool $initialEnableLineItemGrouping;
    private ?string $initialGroupLineItemsBy;
    private ?bool $initialEnableSplitOrders;

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
        $this->initialEnableSplitOrders = $configManager->get(self::ENABLE_SPLIT_ORDERS);

        $configManager->set(self::ENABLE_LINE_ITEM_GROUPING, true);
        $configManager->set(self::GROUP_LINE_ITEMS_BY, 'product.id');
        $configManager->set(self::ENABLE_SPLIT_ORDERS, true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(self::ENABLE_LINE_ITEM_GROUPING, $this->initialEnableLineItemGrouping);
        $configManager->set(self::GROUP_LINE_ITEMS_BY, $this->initialGroupLineItemsBy);
        $configManager->set(self::ENABLE_SPLIT_ORDERS, $this->initialEnableSplitOrders);
        $configManager->flush();
    }

    public function testCompleteCheckoutWithGroupedLineItems(): void
    {
        // Create checkout
        $data = $this->getRequestData('create_checkout_with_full_data.yml');
        unset($data['data']['attributes']['shippingMethod'], $data['data']['attributes']['shippingMethodType']);
        $response = $this->post(
            ['entity' => 'checkouts'],
            $data
        );

        $checkout = $this->getEntityManager()->find(Checkout::class, (int)$this->getResourceId($response));
        $checkoutId = $checkout->getId();
        self::assertNotNull($checkout);

        // Set payment method
        $paymentMethod = $this->getPaymentMethod($checkoutId);
        $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'paymentMethod' => $paymentMethod
                    ]
                ]
            ]
        );

        // Check that checkout is ready for payment
        $validateResponse = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'payment']
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'message' => 'The checkout is ready for payment.',
                    'paymentUrl' => $this->getUrl(
                        'oro_frontend_rest_api_subresource',
                        ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPaymentTerm'],
                        true
                    ),
                    'errors' => []
                ]
            ],
            $validateResponse
        );

        // Pay and place order
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPaymentTerm']
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['data']['relationships']['subOrders']['data']);
    }

    private function getPaymentMethod(int $checkoutId): string
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'availablePaymentMethods']
        );
        $responseData = self::jsonToArray($response->getContent());

        return reset($responseData['data'])['id'];
    }
}
