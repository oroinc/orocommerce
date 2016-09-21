<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as TestAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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

    const ANOTHER_ACCOUNT_ADDRESS = 'account.level_1.address_1';
    const DEFAULT_BILLING_ADDRESS = 'account.level_1.address_2';

    const SHIPPING_ADDRESS_SIGN = 'SELECT SHIPPING ADDRESS';
    const BILLING_ADDRESS_SIGN = 'SELECT BILLING ADDRESS';
    const SHIPPING_METHOD_SIGN = 'Select a Shipping Method';
    const PAYMENT_METHOD_SIGN = 'Payment - Open Order';
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
            $this->generateBasicAuthHeader(TestAccountUserData::AUTH_USER, TestAccountUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                LoadAccountUserData::class,
                LoadAccountAddresses::class,
                LoadProductUnitPrecisions::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadPaymentTermData::class,
                LoadShippingRules::class,
            ]
        );
        $this->registry = $this->getContainer()->get('doctrine');
    }

    /**
     * @param ShoppingList $shoppingList
     * @throws \Oro\Component\Action\Exception\InvalidParameterException
     */
    protected function startCheckout(ShoppingList $shoppingList)
    {
        $this->setCurrentWebsite('default');
        $user = $this->registry
            ->getRepository('OroCustomerBundle:AccountUser')
            ->findOneBy(['username' => TestAccountUserData::AUTH_USER]);
        $user->setAccount($this->getReference('account.level_1'));
        $token = new UsernamePasswordToken($user, false, 'key');
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
        $data = $this->getCheckoutData($shoppingList);
        $action = $this->client->getContainer()->get('oro_checkout.model.action.start_checkout');
        $action->initialize($data['options']);
        $action->execute($data['context']);
        CheckoutControllerTestCase::$checkoutUrl = $data['context']['redirectUrl'];
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    protected function getCheckoutData(ShoppingList $shoppingList)
    {
        $context = new ActionData(['data' => $shoppingList]);

        return [
            'shoppingList' => $shoppingList,
            'context' => $context,
            'options' => [
                StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                StartCheckout::SOURCE_ENTITY_KEY => $shoppingList,
                StartCheckout::CHECKOUT_DATA_KEY => [
                    'poNumber' => 'PO#123' . $shoppingList->getId(),
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

    /**
     * @param Crawler $crawler
     * @param string $type
     * @return null|int
     */
    protected function getSelectedAddressId(Crawler $crawler, $type)
    {
        $select = $crawler->filter(
            sprintf('select[name="%s[%s][accountAddress]"]', CheckoutControllerTestCase::ORO_WORKFLOW_TRANSITION, $type)
        );
        if ($select->filter('option')->count() == 1) {
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
            'accountAddress' => CheckoutControllerTestCase::MANUAL_ADDRESS,
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
    protected function setShippingRuleFormData(array $values)
    {
        /** @var ShippingRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.8');
        /** @var ShippingRuleConfiguration $shippingRuleConfig */
        $shippingRuleConfig = $shippingRule->getConfigurations()->first();
        $values[self::ORO_WORKFLOW_TRANSITION]['shipping_method'] = $shippingRuleConfig->getMethod();
        $values[self::ORO_WORKFLOW_TRANSITION]['shipping_method_type'] = null;
        $values[self::ORO_WORKFLOW_TRANSITION]['shipping_rule_config'] = $shippingRuleConfig->getId();
        $values['_widgetContainer'] = 'ajax';
        $values['_wid'] = 'ajax_checkout';

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
     * @param Crawler $crawler
     * @return Form
     */
    protected function getTransitionForm(Crawler $crawler)
    {
        return $crawler->filter(sprintf('form[name=%s]', CheckoutControllerTestCase::ORO_WORKFLOW_TRANSITION))->form();
    }
}
