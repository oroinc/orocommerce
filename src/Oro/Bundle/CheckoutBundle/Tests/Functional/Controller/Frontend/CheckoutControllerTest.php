<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @group CommunityEdition
 */
class CheckoutControllerTest extends CheckoutControllerTestCase
{
    use EnabledPaymentMethodIdentifierTrait;

    public function testStartCheckout()
    {
        $shoppingList = $this->getSourceEntity();
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);
        self::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());
        self::assertEquals($selectedAddressId, $this->getReference(self::DEFAULT_BILLING_ADDRESS)->getId());
    }

    /**
     * @depends testStartCheckout
     */
    public function testRestartCheckout()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::BILLING_ADDRESS);

        $this->client->getKernel()->shutdown();
        $this->client->getKernel()->boot();
        $this->client->disableReboot();

        /* @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $listener = function (CheckoutValidateEvent $event) {
            $event->setIsCheckoutRestartRequired(true);
        };
        $dispatcher->addListener(CheckoutValidateEvent::NAME, $listener);

        $crawler = $this->client->request('POST', $form->getUri(), $data);
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringNotContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        self::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());

        $dispatcher->removeListener(CheckoutValidateEvent::NAME, $listener);
        $this->client->enableReboot();
    }

    /**
     * @depends testStartCheckout
     */
    public function testSubmitBillingOnManuallyValidation()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $this->setCustomerAddress(self::MANUAL_ADDRESS, $form, self::BILLING_ADDRESS);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());
        $invalidFields = $this->getRequiredFields(self::BILLING_ADDRESS);
        $this->checkValidationErrors($invalidFields, $crawler);
    }

    /**
     * @depends testSubmitBillingOnManuallyValidation
     */
    public function testSubmitBillingOnManually()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::BILLING_ADDRESS);
        $crawler = $this->client->request('POST', $form->getUri(), $data);
        self::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
    }

    /**
     * @depends testSubmitBillingOnManually
     */
    public function testBackToBillingAddressAndSelectExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_BILLING_ADDRESS);
        self::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());
        $this->checkDataPreSet($crawler);
        $form = $this->getTransitionForm($crawler);
        $this->setCustomerAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::BILLING_ADDRESS
        );
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
    }

    /**
     * @depends testBackToBillingAddressAndSelectExistingAddress
     */
    public function testBillingSelectedExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_BILLING_ADDRESS);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);
        self::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());
        self::assertEquals($selectedAddressId, $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId());
    }

    /**
     * @depends testBackToBillingAddressAndSelectExistingAddress
     */
    public function testSubmitShippingOnManuallyValidation()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        $form = $this->getTransitionForm($crawler);
        $this->setCustomerAddress(self::MANUAL_ADDRESS, $form, self::SHIPPING_ADDRESS);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        $invalidFields = $this->getRequiredFields(self::SHIPPING_ADDRESS);
        $this->checkValidationErrors($invalidFields, $crawler);
    }

    /**
     * @depends testSubmitShippingOnManuallyValidation
     */
    public function testSubmitShippingOnManually()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::SHIPPING_ADDRESS);
        $crawler = $this->client->request('POST', $form->getUri(), $data);
        self::assertStringContainsString(self::SHIPPING_METHOD_SIGN, $crawler->html());
    }

    /**
     * @depends testSubmitShippingOnManually
     */
    public function testBackToShippingAddressAndSelectExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_SHIPPING_ADDRESS);
        self::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        $this->checkDataPreSet($crawler);
        $form = $this->getTransitionForm($crawler);
        $this->setCustomerAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::SHIPPING_ADDRESS
        );
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::SHIPPING_METHOD_SIGN, $crawler->html());
    }

    /**
     * @depends testBackToShippingAddressAndSelectExistingAddress
     */
    public function testShippingSelectedExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_SHIPPING_ADDRESS);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::SHIPPING_ADDRESS);
        self::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        self::assertEquals($selectedAddressId, $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId());
    }

    /**
     * @depends testShippingSelectedExistingAddress
     */
    public function testShippingMethodToPaymentTransition()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);

        self::assertStringContainsString(self::SHIPPING_METHOD_SIGN, $crawler->html());
        $form = $this->getTransitionForm($crawler);

        $values = $this->explodeArrayPaths($form->getValues());

        $crawler = $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        self::assertStringContainsString(self::PAYMENT_METHOD_SIGN, $crawler->html());
    }

    private function makePaymentToOrderReviewTransition(): Crawler
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        self::assertStringContainsString(self::PAYMENT_METHOD_SIGN, $crawler->html());

        return $this->submitPaymentTransitionForm($crawler);
    }

    private function submitPaymentTransitionForm(Crawler $crawler): Crawler
    {
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $values[self::ORO_WORKFLOW_TRANSITION]['payment_method'] =
            $this->getPaymentMethodIdentifier($this->getContainer());
        $values['_widgetContainer'] = 'ajax';
        $values['_wid'] = 'ajax_checkout';

        return $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }

    /**
     * @depends testShippingMethodToPaymentTransition
     */
    public function testPaymentToOrderReviewTransition(): Crawler
    {
        $crawler = $this->makePaymentToOrderReviewTransition();

        self::assertStringContainsString(self::ORDER_REVIEW_SIGN, $crawler->html());

        return $crawler;
    }

    /**
     * @depends testPaymentToOrderReviewTransition
     */
    public function testSubmitOrder(Crawler $crawler)
    {
        $sourceEntity = $this->getSourceEntity();
        $sourceEntityId = $sourceEntity->getId();
        $checkoutSources = $this->registry
            ->getRepository(CheckoutSource::class)
            ->findBy(['shoppingList' => $sourceEntity]);

        self::assertCount(1, $checkoutSources);
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertArrayHasKey('successUrl', $data['responseData']);
        self::assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        self::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        self::assertCount(1, $this->registry->getRepository(CheckoutSource::class)->findAll());

        $checkouts = $this->registry->getRepository(Checkout::class)->findAll();

        self::assertCount(1, $checkouts);

        $orders = $this->registry->getRepository(Order::class)->findAll();

        self::assertCount(1, $orders);
        self::assertNull($this->registry->getRepository(ShoppingList::class)->find($sourceEntityId));

        /** @var Checkout $checkout */
        $checkout = array_shift($checkouts);

        /** @var Order $order */
        $order = array_shift($orders);

        self::assertTrue($checkout->isCompleted());
        self::assertEquals(
            [
                'itemsCount' => count($order->getLineItems()),
                'orders' => [
                    [
                        'entityAlias' => 'order',
                        'entityId' => ['id' => $order->getId()]
                    ]
                ],
                'startedFrom' => 'shopping_list_8_label',
                'currency' => $order->getCurrency(),
                'subtotal' => $order->getSubtotal(),
                'total' => $order->getTotal()
            ],
            $checkout->getCompletedData()->getArrayCopy()
        );
    }

    private function checkValidationErrors(array $formFields, Crawler $crawler): void
    {
        foreach ($formFields as $formField) {
            $hasInput = $crawler->filter(sprintf('input[name="%s"]', $formField))->count() > 0;
            $inputType = $hasInput ? 'input' : 'select';
            $fieldData = $crawler->filter(sprintf('%s[name="%s"]', $inputType, $formField))
                ->parents()
                ->parents()
                ->html();
            self::assertStringContainsString('This value should not be blank.', $fieldData);
        }
    }

    private function checkDataPreSet(Crawler $crawler): void
    {
        $html = $crawler->html();
        self::assertStringContainsString(self::FIRST_NAME, $html);
        self::assertStringContainsString(self::LAST_NAME, $html);
        self::assertStringContainsString(self::STREET, $html);
        self::assertStringContainsString(self::POSTAL_CODE, $html);
        self::assertStringContainsString(self::COUNTRY, $html);
        self::assertStringContainsString(self::REGION, $html);
    }

    private function getRequiredFields(string $type): array
    {
        $requiredFields = ['firstName', 'lastName', 'street', 'postalCode', 'country', 'state', 'city'];
        $resultRequiredFields = [];
        foreach ($requiredFields as $requiredField) {
            $requiredFields[] = sprintf('%s[%s][%s]', self::ORO_WORKFLOW_TRANSITION, $type, $requiredField);
        }

        return $resultRequiredFields;
    }

    private function setCustomerAddress(int $addressId, Form $form, string $addressType): void
    {
        $addressId = $addressId === 0 ? '0' : 'a_' . $addressId;

        $addressTypePath = sprintf('%s[%s][customerAddress]', self::ORO_WORKFLOW_TRANSITION, $addressType);
        $form->setValues([$addressTypePath => $addressId]);
    }

    private function getSourceEntity(): ShoppingList
    {
        return $this->getReference(LoadShoppingLists::SHOPPING_LIST_8);
    }
}
