<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as TestCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;

abstract class CheckoutControllerTestCase extends FrontendWebTestCase
{
    const MANUAL_ADDRESS = 0;
    const FIRST_NAME = 'Jackie';
    const LAST_NAME = 'Chuck';
    const STREET = 'Fake Street';
    const CITY = 'Fake City';
    const POSTAL_CODE = '123456';
    const COUNTRY = 'UA';
    const REGION = 'UA-65';

    const ORO_WORKFLOW_TRANSITION = 'oro_workflow_transition';

    const ANOTHER_ACCOUNT_ADDRESS = 'customer.level_1.address_1';
    const DEFAULT_BILLING_ADDRESS = 'customer.level_1.address_2';

    const SHIPPING_ADDRESS_SIGN = 'Select Shipping Address';
    const BILLING_ADDRESS_SIGN = 'Select Billing Address';
    const SHIPPING_METHOD_SIGN = 'Select a Shipping Method';
    const PAYMENT_METHOD_SIGN = 'Payment - Checkout';
    const ORDER_REVIEW_SIGN = 'Do not ship later than';
    const FINISH_SIGN = 'Thank You For Your Purchase!';
    const EDIT_BILLING_SIGN = 'Edit Billing Information';
    const EDIT_SHIPPING_INFO_SIGN = 'Edit Shipping Information';
    const EDIT_SHIPPING_METHOD_SIGN = 'Edit Shipping Method';
    const EDIT_PAYMENT_SIGN = 'Edit Payment';

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

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(TestCustomerUserData::AUTH_USER, TestCustomerUserData::AUTH_PW)
        );
        $paymentFixtures = (array)$this->getPaymentFixtures();
        $inventoryFixtures = (array)$this->getInventoryFixtures();
        $this->loadFixtures(array_merge([
            LoadCustomerUserData::class,
            LoadCustomerAddresses::class,
            LoadProductUnitPrecisions::class,
            LoadShoppingListLineItems::class,
            LoadCombinedProductPrices::class,
            LoadShippingMethodsConfigsRulesWithConfigs::class,
        ], $paymentFixtures, $inventoryFixtures));
        $this->registry = $this->getContainer()->get('doctrine');
    }

    /**
     * @return array
     */
    protected function getPaymentFixtures()
    {
        return [
            LoadPaymentTermData::class,
            LoadPaymentMethodsConfigsRuleData::class
        ];
    }

    /**
     * @return array
     */
    protected function getInventoryFixtures()
    {
        return [UpdateInventoryLevelsQuantities::class];
    }

    protected function startCheckout(ShoppingList $shoppingList)
    {
        $this->startCheckoutByData($this->getCheckoutData($shoppingList));
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    protected function getCheckoutData(ShoppingList $shoppingList)
    {
        return [
            'context' => new ActionData(['shoppingList' => $shoppingList]),
            'options' => [
                'action_group' => 'start_shoppinglist_checkout',
                'parameters_mapping' => [
                    'shoppingList' => $shoppingList,
                    'showErrors' => true,
                ],
                'results' => [
                    'redirectUrl' => new PropertyPath('redirectUrl'),
                ]
            ]
        ];
    }

    /**
     * @param Crawler $crawler
     * @param string $type
     * @return null|int
     */
    protected function getSelectedAddressId(Crawler $crawler, $type)
    {
        $select = $crawler->filter(
            sprintf(
                'select[name="%s[%s][customerAddress]"]',
                CheckoutControllerTestCase::ORO_WORKFLOW_TRANSITION,
                $type
            )
        );
        if ($select->filter('option')->count() === 1) {
            return null;
        } else {
            $value = $select->filter('optgroup')->filter('option[selected="selected"]')->attr('value');

            return (int)substr($value, strpos($value, '_') + 1);
        }
    }

    /**
     * @param array $values
     * @param string $type
     * @return array
     */
    protected function setFormData(array $values, $type)
    {
        $address = [
            'customerAddress' => CheckoutControllerTestCase::MANUAL_ADDRESS,
            'firstName' => CheckoutControllerTestCase::FIRST_NAME,
            'lastName' => CheckoutControllerTestCase::LAST_NAME,
            'street' => CheckoutControllerTestCase::STREET,
            'city' => CheckoutControllerTestCase::CITY,
            'postalCode' => CheckoutControllerTestCase::POSTAL_CODE,
            'country' => CheckoutControllerTestCase::COUNTRY,
            'region' => CheckoutControllerTestCase::REGION,
        ];
        $values[CheckoutControllerTestCase::ORO_WORKFLOW_TRANSITION][$type] = array_merge(
            $values[CheckoutControllerTestCase::ORO_WORKFLOW_TRANSITION][$type],
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
     * @param string $transitionName
     * @return string
     */
    protected function getTransitionUrl($transitionName)
    {
        return sprintf('%s?transition=%s', self::$checkoutUrl, $transitionName);
    }

    /**
     * @param string $transitionName
     * @return Crawler
     */
    protected function getTransitionPage($transitionName)
    {
        $crawler = $this->client->request('GET', $this->getTransitionUrl($transitionName));

        return $crawler;
    }

    /**
     * @param Crawler $crawler
     * @return Form
     */
    protected function getTransitionForm(Crawler $crawler)
    {
        return $crawler->filter(sprintf('form[name=%s]', CheckoutControllerTestCase::ORO_WORKFLOW_TRANSITION))->form();
    }

    /**
     * @param Crawler $crawler
     * @param string  $paymentMethodName
     *
     * @return Crawler
     */
    protected function goToOrderReviewStepFromPayment(Crawler $crawler, $paymentMethodName)
    {
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $values[self::ORO_WORKFLOW_TRANSITION]['payment_method'] = $paymentMethodName;
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

    protected function startCheckoutByData(array $data)
    {
        $userManager = $this->getContainer()->get('oro_customer_user.manager');
        $this->setCurrentWebsite('default');
        $user = $this->registry
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => TestCustomerUserData::AUTH_USER]);
        $user->setCustomer($this->getReference('customer.level_1'));
        $userManager->updateUser($user);

        $organization = $this->registry
            ->getRepository(Organization::class)
            ->getFirst();
        $token = new UsernamePasswordOrganizationToken($user, false, 'key', $organization, $user->getRoles());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
        $action = $this->client->getContainer()->get('oro_action.action.run_action_group');
        $action->initialize($data['options']);
        $action->execute($data['context']);
        self::$checkoutUrl = $data['context']['redirectUrl'];
    }
}
