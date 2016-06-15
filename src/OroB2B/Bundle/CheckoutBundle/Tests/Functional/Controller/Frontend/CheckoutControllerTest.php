<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @dbIsolation
 */
class CheckoutControllerTest extends CheckoutControllerTestCase
{
    public function testStartCheckout()
    {
        $this->startCheckout($this->getSourceEntity());
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);
        $this->assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());
        $this->assertEquals($selectedAddressId, $this->getReference(self::DEFAULT_BILLING_ADDRESS)->getId());
    }

    /**
     * @depends testStartCheckout
     */
    public function testSubmitBillingOnManuallyValidation()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $this->setAccountAddress(self::MANUAL_ADDRESS, $form, self::BILLING_ADDRESS);
        $crawler = $this->client->submit($form);
        $this->assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());
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
        $this->assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
    }

    /**
     * @depends testSubmitBillingOnManually
     */
    public function testBackToBillingAddressAndSelectExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_BILLING_ADDRESS);
        $this->assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());
        $this->checkDataPreSet($crawler);
        $form = $this->getTransitionForm($crawler);
        $this->setAccountAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::BILLING_ADDRESS
        );
        $crawler = $this->client->submit($form);
        $this->assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
    }

    /**
     * @depends testBackToBillingAddressAndSelectExistingAddress
     */
    public function testBillingSelectedExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_BILLING_ADDRESS);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);
        $this->assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());
        $this->assertEquals($selectedAddressId, $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId());
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
        $this->setAccountAddress(self::MANUAL_ADDRESS, $form, self::SHIPPING_ADDRESS);
        $crawler = $this->client->submit($form);
        $this->assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
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
        $this->assertContains(self::SHIPPING_METHOD_SIGN, $crawler->html());
    }

    /**
     * @depends testSubmitShippingOnManually
     */
    public function testBackToShippingAddressAndSelectExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_SHIPPING_ADDRESS);
        $this->assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        $this->checkDataPreSet($crawler);
        $form = $this->getTransitionForm($crawler);
        $this->setAccountAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::SHIPPING_ADDRESS
        );
        $crawler = $this->client->submit($form);
        $this->assertContains(self::SHIPPING_METHOD_SIGN, $crawler->html());
    }

    /**
     * @depends testBackToShippingAddressAndSelectExistingAddress
     */
    public function testShippingSelectedExistingAddress()
    {
        $crawler = $this->getTransitionPage(self::TRANSITION_BACK_TO_SHIPPING_ADDRESS);
        $selectedAddressId = $this->getSelectedAddressId($crawler, self::SHIPPING_ADDRESS);
        $this->assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());
        $this->assertEquals($selectedAddressId, $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId());
    }

    /**
     * @depends testShippingSelectedExistingAddress
     */
    public function testShippingMethodToPaymentTransition()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);

        $this->assertContains(self::SHIPPING_METHOD_SIGN, $crawler->html());
        $form = $this->getFakeForm($crawler);
        $crawler = $this->client->submit($form);
        $this->assertContains(self::PAYMENT_METHOD_SIGN, $crawler->html());
    }

    /**
     * @depends testShippingMethodToPaymentTransition
     * @return Crawler
     */
    public function testPaymentToOrderReviewTransition()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $this->assertContains(self::PAYMENT_METHOD_SIGN, $crawler->html());
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $values[self::ORO_WORKFLOW_TRANSITION]['payment_method'] = 'payment_term';
        $values['_widgetContainer'] = 'ajax';
        $values['_wid'] = 'ajax_checkout';

        $crawler = $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertContains(self::ORDER_REVIEW_SIGN, $crawler->html());

        return $crawler;
    }

    /**
     * @depends testPaymentToOrderReviewTransition
     * @param Crawler $crawler
     */
    public function testSubmitOrder(Crawler $crawler)
    {
        $sourceEntity = $this->getSourceEntity();
        $sourceEntityId = $sourceEntity->getId();
        $checkoutSources = $this->registry
            ->getRepository('OroB2BCheckoutBundle:CheckoutSource')
            ->findBy(['shoppingList' => $sourceEntity]);

        $this->assertCount(1, $checkoutSources);
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        $this->assertContains(self::FINISH_SIGN, $crawler->html());
        $this->assertCount(0, $this->registry->getRepository('OroB2BCheckoutBundle:CheckoutSource')->findAll());
        $this->assertNull(
            $this->registry->getRepository('OroB2BShoppingListBundle:ShoppingList')->find($sourceEntityId)
        );
    }

    /**
     * @param array $formFields
     * @param Crawler $crawler
     */
    protected function checkValidationErrors(array $formFields, Crawler $crawler)
    {
        foreach ($formFields as $formField) {
            $hasInput = $crawler->filter(sprintf('input[name="%s"]', $formField))->count() > 0;
            $inputType = $hasInput ? 'input' : 'select';
            $fieldData = $crawler->filter(sprintf('%s[name="%s"]', $inputType, $formField))
                ->parents()
                ->parents()
                ->html();
            $this->assertContains('This value should not be blank.', $fieldData);
        }
    }

    /**
     * @param Crawler $crawler
     */
    protected function checkDataPreSet(Crawler $crawler)
    {
        $html = $crawler->html();
        $this->assertContains(self::FIRST_NAME, $html);
        $this->assertContains(self::LAST_NAME, $html);
        $this->assertContains(self::STREET, $html);
        $this->assertContains(self::POSTAL_CODE, $html);
        $this->assertContains(self::COUNTRY, $html);
        $this->assertContains(self::REGION, $html);
    }

    /**
     * @param Account $account
     */
    protected function setCurrentAccountOnAddresses(Account $account)
    {
        $addresses = $this->registry->getRepository('OroB2BAccountBundle:AccountAddress')->findAll();
        foreach ($addresses as $address) {
            $address->setFrontendOwner($account);
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
    protected function setAccountAddress($addressId, Form $form, $addressType)
    {
        $addressId = $addressId == 0 ?: 'a_' . $addressId;

        $addressTypePath = sprintf('%s[%s][accountAddress]', self::ORO_WORKFLOW_TRANSITION, $addressType);
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
     * @param string $transitionName
     * @return Crawler
     */
    protected function getTransitionPage($transitionName)
    {
        $crawler = $this->client->request('GET', sprintf('%s?transition=%s', self::$checkoutUrl, $transitionName));

        return $crawler;
    }

    /**
     * @return ShoppingList
     */
    protected function getSourceEntity()
    {
        return $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
    }
}
