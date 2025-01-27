<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdditionalCompletionCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class CheckoutPaymentTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadAdditionalCompletionCheckoutData::class,
            LoadCompetedCheckoutData::class
        ]);
    }

    private function prepareCheckoutForPayment(int $checkoutId): void
    {
        $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'paymentMethod' => '@checkout.completed->paymentMethod',
                        'shippingMethod' => '@checkout.completed->shippingMethod',
                        'shippingMethodType' => 'primary'
                    ]
                ]
            ]
        );
    }

    private function getPaymentUrl(int $checkoutId): string
    {
        return $this->getUrl(
            $this->getSubresourceRouteName(),
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPaymentTerm'],
            true
        );
    }

    public function testGetPaymentForEmptyCheckout(): void
    {
        $checkoutId = $this->getReference('checkout.empty')->getId();
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'payment']
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'message' => 'The checkout is not ready for payment.',
                    'paymentUrl' => null,
                    'errors' => [
                        [
                            'title' => 'not blank constraint',
                            'detail' => 'Please enter correct billing address.',
                            'source' => ['pointer' => '/data/relationships/billingAddress/data']
                        ],
                        [
                            'title' => 'not empty shipping address constraint',
                            'detail' => 'Please enter correct shipping address.',
                            'source' => ['pointer' => '/data/relationships/shippingAddress/data']
                        ],
                        [
                            'title' => 'not blank constraint',
                            'detail' => 'Shipping method is not selected.',
                            'source' => ['pointer' => '/data/attributes/shippingMethod']
                        ],
                        [
                            'title' => 'not blank constraint',
                            'detail' => 'Payment method is not selected.',
                            'source' => ['pointer' => '/data/attributes/paymentMethod']
                        ],
                        [
                            'title' => 'count constraint',
                            'detail' => 'This collection should contain 1 element or more.',
                            'source' => ['pointer' => '/data/relationships/lineItems/data']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetPaymentForReadyForCompletionCheckout(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'payment']
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'message' => 'The checkout is ready for payment.',
                    'paymentUrl' => $this->getPaymentUrl($checkoutId),
                    'errors' => []
                ]
            ],
            $response
        );
    }

    public function testGetPaymentForReadyForCompletionCheckoutWithShipToBillingAddress(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion.ship_to_billing_address')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'payment']
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'message' => 'The checkout is ready for payment.',
                    'paymentUrl' => $this->getPaymentUrl($checkoutId),
                    'errors' => []
                ]
            ],
            $response
        );
    }

    public function testGetPaymentForReadyForCompletionCheckoutWithoutShipToBillingAddressAndShippingAddress(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion.no_ship_to_billing_address')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'payment']
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'message' => 'The checkout is not ready for payment.',
                    'paymentUrl' => null,
                    'errors' => [
                        [
                            'title' => 'not empty shipping address constraint',
                            'detail' => 'Please enter correct shipping address.',
                            'source' => ['pointer' => '/data/relationships/shippingAddress/data']
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
