<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class ValidatePaymentTest extends FrontendRestJsonApiTestCase
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

    private function getShippingMethodInfo(int $checkoutId): array
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'availableShippingMethods']
        );
        $responseData = self::jsonToArray($response->getContent());
        $shippingMethodData = $responseData['data'][0];
        $shippingMethodTypeData = reset($shippingMethodData['attributes']['types']);

        return ['id' => $shippingMethodData['id'], 'type' => $shippingMethodTypeData['id']];
    }

    private function getPaymentMethodId(int $checkoutId): string
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'availablePaymentMethods']
        );
        $responseData = self::jsonToArray($response->getContent());
        foreach ($responseData['data'] as $paymentMethod) {
            if (str_starts_with($paymentMethod['id'], 'payment_term')) {
                return $paymentMethod['id'];
            }
        }
        throw new \RuntimeException('The Payment Term payment method was not found.');
    }

    public function testValidatePaymentForNotReadyToPaymentCheckout(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();

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
                            'status' => '400',
                            'title' => 'not blank constraint',
                            'detail' => 'Shipping method is not selected.',
                            'source' => ['pointer' => '/data/attributes/shippingMethod']
                        ],
                        [
                            'status' => '400',
                            'title' => 'not blank constraint',
                            'detail' => 'Payment method is not selected.',
                            'source' => ['pointer' => '/data/attributes/paymentMethod']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testValidatePaymentForReadyToPaymentCheckout(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();

        // set shipping and payment methods to the checkout
        $shippingMethodInfo = $this->getShippingMethodInfo($checkoutId);
        $paymentMethodId = $this->getPaymentMethodId($checkoutId);
        $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'shippingMethod' => $shippingMethodInfo['id'],
                        'shippingMethodType' => $shippingMethodInfo['type'],
                        'paymentMethod' => $paymentMethodId
                    ]
                ]
            ]
        );

        $response = $this->getSubresource(
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
            $response
        );
    }
}
