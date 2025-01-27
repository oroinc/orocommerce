<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class PaymentTermPaymentTest extends FrontendRestJsonApiTestCase
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

    private function getPaymentMethod(int $checkoutId): string
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

    private function getShippingMethod(int $checkoutId): array
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'availableShippingMethods']
        );
        $responseData = self::jsonToArray($response->getContent());
        $shippingMethodData = $responseData['data'][0];
        $shippingMethodTypeData = reset($shippingMethodData['attributes']['types']);

        return [$shippingMethodData['id'], $shippingMethodTypeData['id']];
    }

    private function prepareCheckoutForPayment(
        int $checkoutId,
        string $paymentMethod,
        string $shippingMethod,
        string $shippingMethodType
    ): void {
        $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'paymentMethod' => $paymentMethod,
                        'shippingMethod' => $shippingMethod,
                        'shippingMethodType' => $shippingMethodType
                    ]
                ]
            ]
        );
    }

    public function testTryToPaymentTermPaymentForNotReadyToPaymentCheckout(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPaymentTerm'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'payment constraint',
                'detail' => 'The checkout is not ready for payment.',
                'meta' => [
                    'validatePaymentUrl' => $this->getUrl(
                        'oro_frontend_rest_api_subresource',
                        ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'payment'],
                        true
                    )
                ]
            ],
            $response
        );
    }

    public function testPaymentTermPayment(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $paymentMethod = $this->getPaymentMethod($checkoutId);
        [$shippingMethod, $shippingMethodType] = $this->getShippingMethod($checkoutId);
        $this->prepareCheckoutForPayment($checkoutId, $paymentMethod, $shippingMethod, $shippingMethodType);

        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPaymentTerm']
        );
        $responseData = self::jsonToArray($response->getContent());
        $this->assertResponseContains('order_for_ready_for_completion_checkout.yml', $response);
        self::assertEquals($paymentMethod, $responseData['data']['attributes']['paymentMethod'][0]['code']);
        self::assertEquals($shippingMethod, $responseData['data']['attributes']['shippingMethod']['code']);
        self::assertEquals($shippingMethodType, $responseData['data']['attributes']['shippingMethod']['type']);
        self::assertNotEmpty($responseData['data']['relationships']['billingAddress']['data']);
        self::assertNotEmpty($responseData['data']['relationships']['shippingAddress']['data']);
        self::assertCount(1, $responseData['data']['relationships']['lineItems']['data']);
    }
}
