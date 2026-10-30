<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;

/**
 * Opens and submits the "new address" operation forms of the storefront checkout.
 */
abstract class CheckoutNewAddressOperationAclTestCase extends FrontendWebTestCase
{
    protected const string MULTI_STEP_BILLING = 'b2b_flow_checkout_new_billing_address';
    protected const string MULTI_STEP_SHIPPING = 'b2b_flow_checkout_new_shipping_address';
    protected const string SINGLE_PAGE_BILLING = 'b2b_flow_checkout_single_page_new_billing_address';
    protected const string SINGLE_PAGE_SHIPPING = 'b2b_flow_checkout_single_page_new_shipping_address';

    private const array ADDRESS = [
        'firstName' => 'Changed',
        'lastName' => 'Doe',
        'street' => '1 Main St',
        'city' => 'Los Angeles',
        'postalCode' => '90001',
        'country' => 'US',
        'region' => 'US-CA',
    ];

    protected function getFormUrl(string $operationName, string $checkoutReference): string
    {
        return $this->getUrl('oro_frontend_action_widget_form', [
            'operationName' => $operationName,
            'entityClass' => Checkout::class,
            'entityId' => $this->getReference($checkoutReference)->getId(),
            '_widgetContainer' => 'dialog',
            '_wid' => 'test-widget',
        ]);
    }

    protected function assertFormCanBeOpenedAndSubmitted(string $operationName, string $checkoutReference): void
    {
        $crawler = $this->client->request('GET', $this->getFormUrl($operationName, $checkoutReference));
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->filter('form[name="oro_action_operation"]')->form();
        // The region options are loaded by JS after a country is selected.
        $form->disableValidation();
        foreach (self::ADDRESS as $field => $value) {
            $form->offsetSet('oro_action_operation[address][' . $field . ']', $value);
        }
        // The billing address form requires an email from a visitor.
        if ($form->has('oro_action_operation[visitor_email]')) {
            $form->offsetSet('oro_action_operation[visitor_email]', 'visitor@example.com');
        }
        $this->client->submit($form, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('"success":true', $this->client->getResponse()->getContent());
        self::assertSame('Changed', $this->getAddressFirstName($operationName, $checkoutReference));
    }

    /**
     * Submits the form with a CSRF token taken from a checkout the current user may edit,
     * so only the access check can reject the request.
     */
    protected function assertFormCannotBeOpenedOrSubmitted(
        string $operationName,
        string $checkoutReference,
        string $ownCheckoutReference
    ): void {
        $this->client->request('GET', $this->getFormUrl($operationName, $checkoutReference));
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 403);

        $crawler = $this->client->request(
            'GET',
            $this->getFormUrl(self::MULTI_STEP_BILLING, $ownCheckoutReference)
        );
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $csrfToken = $crawler->filter('input[name="oro_action_operation[_token]"]')->attr('value');

        $this->client->request(
            'POST',
            $this->getFormUrl($operationName, $checkoutReference),
            ['oro_action_operation' => ['address' => self::ADDRESS, '_token' => $csrfToken]],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 403);
        self::assertNull($this->getAddressFirstName($operationName, $checkoutReference));
    }

    private function getAddressFirstName(string $operationName, string $checkoutReference): ?string
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Checkout::class);
        $entityManager->clear();
        /** @var Checkout $checkout */
        $checkout = $entityManager->find(Checkout::class, $this->getReference($checkoutReference)->getId());
        $address = \in_array($operationName, [self::MULTI_STEP_BILLING, self::SINGLE_PAGE_BILLING], true)
            ? $checkout->getBillingAddress()
            : $checkout->getShippingAddress();

        return $address?->getFirstName();
    }
}
