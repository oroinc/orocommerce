<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
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
    use EnabledPaymentMethodIdentifierTrait {
        getReference as protected;
    }

    public function testStartCheckout()
    {
        $shoppingList = $this->getSourceEntity();
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);
        static::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());
        static::assertEquals($selectedAddressId, $this->getReference(self::DEFAULT_BILLING_ADDRESS)->getId());
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

        /* @var $dispatcher EventDispatcherInterface */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $listener = function (CheckoutValidateEvent $event) {
            $event->setIsCheckoutRestartRequired(true);
        };
        $dispatcher->addListener(CheckoutValidateEvent::NAME, $listener);

        $crawler = $this->client->request('POST', $form->getUri(), $data);
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringNotContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        static::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());

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
        static::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());
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
        static::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
    }

    /**
     * @depends testSubmitBillingOnManually
     */
    public function testBackToBillingAddressAndSelectExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_BILLING_ADDRESS);
        static::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());
        $this->checkDataPreSet($crawler);
        $form = $this->getTransitionForm($crawler);
        $this->setCustomerAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::BILLING_ADDRESS
        );
        $crawler = $this->client->submit($form);
        static::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
    }

    /**
     * @depends testBackToBillingAddressAndSelectExistingAddress
     */
    public function testBillingSelectedExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_BILLING_ADDRESS);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);
        static::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());
        static::assertEquals($selectedAddressId, $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId());
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
        static::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
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
        static::assertStringContainsString(self::SHIPPING_METHOD_SIGN, $crawler->html());
    }

    /**
     * @depends testSubmitShippingOnManually
     */
    public function testBackToShippingAddressAndSelectExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_SHIPPING_ADDRESS);
        static::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        $this->checkDataPreSet($crawler);
        $form = $this->getTransitionForm($crawler);
        $this->setCustomerAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::SHIPPING_ADDRESS
        );
        $crawler = $this->client->submit($form);
        static::assertStringContainsString(self::SHIPPING_METHOD_SIGN, $crawler->html());
    }

    /**
     * @depends testBackToShippingAddressAndSelectExistingAddress
     */
    public function testShippingSelectedExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_SHIPPING_ADDRESS);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::SHIPPING_ADDRESS);
        static::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        static::assertEquals($selectedAddressId, $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId());
    }

    /**
     * @depends testShippingSelectedExistingAddress
     */
    public function testShippingMethodToPaymentTransition()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);

        static::assertStringContainsString(self::SHIPPING_METHOD_SIGN, $crawler->html());
        $form = $this->getTransitionForm($crawler);

        $values = $this->explodeArrayPaths($form->getValues());

        $crawler = $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        static::assertStringContainsString(self::PAYMENT_METHOD_SIGN, $crawler->html());
    }

    /**
     * @return null|Crawler
     */
    protected function makePaymentToOrderReviewTransition()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        static::assertStringContainsString(self::PAYMENT_METHOD_SIGN, $crawler->html());

        return $this->submitPaymentTransitionForm($crawler);
    }

    /**
     * @param Crawler $crawler
     * @return Crawler
     */
    protected function submitPaymentTransitionForm(Crawler $crawler)
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
     * @return Crawler
     */
    public function testPaymentToOrderReviewTransition()
    {
        $crawler = $this->makePaymentToOrderReviewTransition();

        static::assertStringContainsString(self::ORDER_REVIEW_SIGN, $crawler->html());

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
            ->getRepository('OroCheckoutBundle:CheckoutSource')
            ->findBy(['shoppingList' => $sourceEntity]);

        static::assertCount(1, $checkoutSources);
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = static::getJsonResponseContent($this->client->getResponse(), 200);
        static::assertArrayHasKey('successUrl', $data['responseData']);
        static::assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        static::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        static::assertCount(1, $this->registry->getRepository('OroCheckoutBundle:CheckoutSource')->findAll());

        $checkouts = $this->registry->getRepository('OroCheckoutBundle:Checkout')->findAll();

        static::assertCount(1, $checkouts);

        $orders = $this->registry->getRepository('OroOrderBundle:Order')->findAll();

        static::assertCount(1, $orders);
        static::assertNull($this->registry->getRepository('OroShoppingListBundle:ShoppingList')->find($sourceEntityId));

        /** @var Checkout $checkout */
        $checkout = array_shift($checkouts);

        /** @var Order $order */
        $order = array_shift($orders);

        static::assertTrue($checkout->isCompleted());
        static::assertEquals(
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

    protected function checkValidationErrors(array $formFields, Crawler $crawler)
    {
        foreach ($formFields as $formField) {
            $hasInput = $crawler->filter(sprintf('input[name="%s"]', $formField))->count() > 0;
            $inputType = $hasInput ? 'input' : 'select';
            $fieldData = $crawler->filter(sprintf('%s[name="%s"]', $inputType, $formField))
                ->parents()
                ->parents()
                ->html();
            static::assertStringContainsString('This value should not be blank.', $fieldData);
        }
    }

    protected function checkDataPreSet(Crawler $crawler)
    {
        $html = $crawler->html();
        static::assertStringContainsString(self::FIRST_NAME, $html);
        static::assertStringContainsString(self::LAST_NAME, $html);
        static::assertStringContainsString(self::STREET, $html);
        static::assertStringContainsString(self::POSTAL_CODE, $html);
        static::assertStringContainsString(self::COUNTRY, $html);
        static::assertStringContainsString(self::REGION, $html);
    }

    protected function setCurrentCustomerOnAddresses(Customer $customer)
    {
        $addresses = $this->registry->getRepository('OroCustomerBundle:CustomerAddress')->findAll();
        /** @var CustomerAddress $address */
        foreach ($addresses as $address) {
            $address->setFrontendOwner($customer);
        }
        $this->registry->getManager()->flush();
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getRequiredFields($type)
    {
        $requiredFields = ['firstName', 'lastName', 'street', 'postalCode', 'country', 'state', 'city'];
        $resultRequiredFields = [];
        foreach ($requiredFields as $requiredField) {
            $requiredFields[] = sprintf('%s[%s][%s]', self::ORO_WORKFLOW_TRANSITION, $type, $requiredField);
        }

        return $resultRequiredFields;
    }

    /**
     * @param integer $addressId
     * @param Form $form
     * @param string $addressType
     */
    protected function setCustomerAddress($addressId, Form $form, $addressType)
    {
        $addressId = $addressId == 0 ?: 'a_' . $addressId;

        $addressTypePath = sprintf('%s[%s][customerAddress]', self::ORO_WORKFLOW_TRANSITION, $addressType);
        $form->setValues([$addressTypePath => $addressId]);
    }

    /**
     * @param Crawler $crawler
     * @return Form
     */
    protected function getFakeForm(Crawler $crawler)
    {
        return $crawler->filter('form')->form();
    }

    /**
     * @return ShoppingList|object
     */
    protected function getSourceEntity()
    {
        return $this->getReference(LoadShoppingLists::SHOPPING_LIST_8);
    }
}
