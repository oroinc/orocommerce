<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyPath;

abstract class CheckoutControllerTestCase extends FrontendWebTestCase
{
    protected const MANUAL_ADDRESS = 0;
    protected const FIRST_NAME = 'Jackie';
    protected const LAST_NAME = 'Chuck';
    protected const STREET = 'Fake Street';
    protected const CITY = 'Fake City';
    protected const POSTAL_CODE = '123456';
    protected const COUNTRY = 'UA';
    protected const REGION = 'UA-65';

    protected const ORO_WORKFLOW_TRANSITION = 'oro_workflow_transition';

    protected const ANOTHER_ACCOUNT_ADDRESS = 'customer.level_1.address_1';
    protected const DEFAULT_BILLING_ADDRESS = 'customer.level_1.address_2';

    protected const SHIPPING_ADDRESS_SIGN = 'Select Shipping Address';
    protected const BILLING_ADDRESS_SIGN = 'Select Billing Address';
    protected const SHIPPING_METHOD_SIGN = 'Select a Shipping Method';
    protected const PAYMENT_METHOD_SIGN = 'Payment - Checkout';
    protected const ORDER_REVIEW_SIGN = 'Do not ship later than';
    protected const FINISH_SIGN = 'Thank You For Your Purchase!';
    protected const EDIT_BILLING_SIGN = 'Edit Billing Information';
    protected const EDIT_SHIPPING_INFO_SIGN = 'Edit Shipping Information';
    protected const EDIT_SHIPPING_METHOD_SIGN = 'Edit Shipping Method';
    protected const EDIT_PAYMENT_SIGN = 'Edit Payment';

    protected const SHIPPING_ADDRESS = 'shipping_address';
    protected const BILLING_ADDRESS = 'billing_address';

    protected const TRANSITION_BACK_TO_BILLING_ADDRESS = 'back_to_billing_address';
    protected const TRANSITION_BACK_TO_SHIPPING_ADDRESS = 'back_to_shipping_address';

    protected ManagerRegistry $registry;
    protected static ?string $checkoutUrl = null;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(TestCustomerUserData::AUTH_USER, TestCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(array_merge([
            LoadCustomerUserData::class,
            LoadCustomerAddresses::class,
            LoadProductUnitPrecisions::class,
            LoadShoppingListLineItems::class,
            LoadCombinedProductPrices::class,
            LoadShippingMethodsConfigsRulesWithConfigs::class,
        ], $this->getPaymentFixtures(), $this->getInventoryFixtures()));
        $this->registry = $this->getContainer()->get('doctrine');
    }

    protected function getPaymentFixtures(): array
    {
        return [
            LoadPaymentTermData::class,
            LoadPaymentMethodsConfigsRuleData::class
        ];
    }

    protected function getInventoryFixtures(): array
    {
        return [UpdateInventoryLevelsQuantities::class];
    }

    protected function startCheckout(ShoppingList $shoppingList)
    {
        $this->startCheckoutByData($this->getCheckoutData($shoppingList));
    }

    protected function getCheckoutData(ShoppingList $shoppingList): array
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

    protected function getSelectedAddressId(Crawler $crawler, string $type): ?int
    {
        $select = $crawler->filter(
            sprintf('select[name="%s[%s][customerAddress]"]', static::ORO_WORKFLOW_TRANSITION, $type)
        );
        if ($select->filter('option')->count() === 1) {
            return null;
        }

        $value = $select->filter('optgroup')->filter('option[selected="selected"]')->attr('value');

        return (int)substr($value, strpos($value, '_') + 1);
    }

    protected function setFormData(array $values, string $type): array
    {
        $address = [
            'customerAddress' => static::MANUAL_ADDRESS,
            'firstName' => static::FIRST_NAME,
            'lastName' => static::LAST_NAME,
            'street' => static::STREET,
            'city' => static::CITY,
            'postalCode' => static::POSTAL_CODE,
            'country' => static::COUNTRY,
            'region' => static::REGION,
        ];
        $values[static::ORO_WORKFLOW_TRANSITION][$type] = array_merge(
            $values[static::ORO_WORKFLOW_TRANSITION][$type],
            $address
        );

        return $values;
    }

    protected function explodeArrayPaths(array $values): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $parameters = [];
        foreach ($values as $key => $val) {
            $pos = strpos($key, '[');
            if (!$pos) {
                continue;
            }
            $key = '[' . substr($key, 0, $pos) . ']' . substr($key, $pos);
            $accessor->setValue($parameters, $key, $val);
        }

        return $parameters;
    }

    protected function getTransitionUrl(string $transitionName): string
    {
        return sprintf('%s?transition=%s', self::$checkoutUrl, $transitionName);
    }

    protected function getTransitionPage(string $transitionName): Crawler
    {
        return $this->client->request('GET', $this->getTransitionUrl($transitionName));
    }

    protected function getTransitionForm(Crawler $crawler): Form
    {
        return $crawler->filter(sprintf('form[name=%s]', static::ORO_WORKFLOW_TRANSITION))->form();
    }

    protected function goToOrderReviewStepFromPayment(Crawler $crawler, string $paymentMethodName): Crawler
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
        $token = new UsernamePasswordOrganizationToken($user, false, 'key', $organization, $user->getUserRoles());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
        $action = $this->client->getContainer()->get('oro_action.action.run_action_group');
        $action->initialize($data['options']);
        $action->execute($data['context']);
        self::$checkoutUrl = $data['context']['redirectUrl'];
    }
}
