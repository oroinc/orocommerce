<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @dbIsolation
 */
class CheckoutControllerTest extends WebTestCase
{
    const MANUAL_ADDRESS = 0;
    const FIRST_NAME = 'Jackie';
    const LAST_NAME = 'Chuck';
    const STREET = 'Fake Street';
    const POSTAL_CODE = '123456';
    const COUNTRY = 'UA';
    const REGION = 'UA-65';

    const ORO_WORKFLOW_TRANSITION = 'oro_workflow_transition';

    const ANOTHER_ACCOUNT_ADDRESS = 'account.level_1.address_1';
    const DEFAULT_BILLING_ADDRESS = 'account.level_1.address_2';

    const SHIPPING_ADDRESS_SIGN = 'SELECT SHIPPING ADDRESS';
    const BILLING_ADDRESS_SIGN = 'SELECT BILLING ADDRESS';
    const SHIPPING_METHOD_SIGN = 'Select a Shipping Method';
    const PAYMENT_METHOD_SIGN = 'Payment - Checkout';
    const ORDER_REVIEW_SIGN = 'View Options for this Order';
    const FINISH_SIGN = 'Thank You For Your Purchase!';

    const SHIPPING_ADDRESS = 'shipping_address';
    const BILLING_ADDRESS = 'billing_address';

    const TRANSITION_BACK_TO_BILLING_ADDRESS = 'back_to_billing_address';
    const TRANSITION_BACK_TO_SHIPPING_ADDRESS = 'back_to_shipping_address';

    /** @var  ManagerRegistry */
    protected $registry;

    /**
     * @var string
     */
    protected static $checkoutUrl;

    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
            ]
        );
        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testStartCheckout()
    {
        $this->startCheckout();
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
        /** @var ShoppingList $sourceEntity */
        $sourceEntity = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $sourceEntityId = $sourceEntity->getId();
        $checkoutSources = $this->registry
            ->getRepository('OroB2BCheckoutBundle:CheckoutSource')
            ->findBy(['shoppingList' => $sourceEntity]);

        $this->assertCount(1, $checkoutSources);
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);
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
     * @param Crawler $crawler
     * @param string $type
     * @return null|int
     */
    protected function getSelectedAddressId(Crawler $crawler, $type)
    {
        $select = $crawler->filter(
            sprintf('select[name="%s[%s][accountAddress]"]', self::ORO_WORKFLOW_TRANSITION, $type)
        );
        if ($select->filter('option')->count() == 1) {
            return null;
        } else {
            $value = $select->filter('optgroup')->filter('option[selected="selected"]')->attr('value');

            return (int)substr($value, strpos($value, '_') + 1);
        }
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
     * @param array $values
     * @param string $type
     * @return array
     */
    protected function setFormData(array $values, $type)
    {
        $address = [
            'accountAddress' => self::MANUAL_ADDRESS,
            'firstName' => self::FIRST_NAME,
            'lastName' => self::LAST_NAME,
            'street' => self::STREET,
            'postalCode' => self::POSTAL_CODE,
            'country' => self::COUNTRY,
            'region' => self::REGION,
        ];
        $values[self::ORO_WORKFLOW_TRANSITION][$type] = array_merge(
            $values[self::ORO_WORKFLOW_TRANSITION][$type],
            $address
        );

        return $values;
    }

    /**
     * @param array $values
     * @return array
     */
    protected function explodeArrayPaths($values)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $parameters = [];
        foreach ($values as $key => $val) {
            if (!$pos = strpos($key, '[')) {
                continue;
            }
            $key = '[' . substr($key, 0, $pos) . ']' . substr($key, $pos);
            $accessor->setValue($parameters, $key, $val);
        }

        return $parameters;
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getRequiredFields($type)
    {
        $requiredFields = ['firstName', 'lastName', 'street', 'postalCode', 'country', 'state'];
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
        $form[$addressTypePath] = $addressId;
    }

    /**
     * @param Crawler $crawler
     * @return Form
     */
    protected function getTransitionForm(Crawler $crawler)
    {
        return $crawler->filter(sprintf('form[name=%s]', self::ORO_WORKFLOW_TRANSITION))->form();
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

    protected function startCheckout()
    {
        $user = $this->registry
            ->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
        $user->setAccount($this->getReference('account.level_1'));
        $token = new UsernamePasswordToken($user, false, 'key');
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
        $data = $this->getCheckoutData();
        $action = $this->client->getContainer()->get('orob2b_checkout.model.action.start_checkout');
        $action->initialize($data['options']);
        $action->execute($data['context']);
        self::$checkoutUrl = $data['context']['redirectUrl'];
    }

    /**
     * @return array
     */
    protected function getCheckoutData()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $context = new ActionData(['data' => $shoppingList]);

        return [
            'shoppingList' => $shoppingList,
            'context' => $context,
            'options' => [
                StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                StartCheckout::SOURCE_ENTITY_KEY => $shoppingList,
                StartCheckout::CHECKOUT_DATA_KEY => [
                    'poNumber' => 'PO#123',
                    'currency' => 'EUR'
                ],
                StartCheckout::SETTINGS_KEY => [
                    'auto_remove_source' => true,
                    'disallow_billing_address_edit' => false,
                    'disallow_shipping_address_edit' => false,
                    'remove_source' => false
                ]
            ]
        ];
    }
}
