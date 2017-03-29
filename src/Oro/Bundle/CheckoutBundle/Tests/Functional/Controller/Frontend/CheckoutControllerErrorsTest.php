<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolationPerTest
 */
class CheckoutControllerErrorsTest extends CheckoutControllerTestCase
{
    use EnabledPaymentMethodIdentifierTrait;

    public function setUp()
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                LoadCustomerAddresses::class,
                LoadProductUnitPrecisions::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadPaymentTermData::class,
                LoadPaymentMethodsConfigsRuleData::class,
                LoadShippingMethodsConfigsRulesWithConfigs::class,
                UpdateInventoryLevelsQuantities::class
            ]
        );
        $this->registry = static::getContainer()->get('doctrine');
    }

    public function testStartCheckoutProductsWithoutPrices()
    {
        $translator = static::getContainer()->get('translator');
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $this->startCheckout($shoppingList);
        static::assertNull(self::$checkoutUrl);

        $flashBag = static::getContainer()->get('session.flash_bag');
        $noItemsWithPriceError = $translator
            ->trans('oro.frontend.shoppinglist.messages.cannot_create_order_no_line_item_with_price');
        static::assertTrue($flashBag->has('error'));
        static::assertContains($noItemsWithPriceError, $flashBag->get('error'));
    }

    public function testStartCheckoutSeveralProductsWithoutPrices()
    {
        $translator = static::getContainer()->get('translator');
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $noProductsError = $translator
            ->trans('oro.checkout.order.line_items.line_item_has_no_price.message');
        static::assertContains($noProductsError, $crawler->html());

        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::BILLING_ADDRESS);
        $crawler = $this->client->request('POST', $form->getUri(), $data);
        static::assertContains($noProductsError, $crawler->html());

        $productId = $this->getReference(LoadProductData::PRODUCT_5)->getId();
        $url = $this->getUrl('oro_shopping_list_frontend_remove_product', [
            'productId' => $productId,
            'shoppingListId' => $shoppingList->getId(),
        ]);
        $this->client->request('POST', $url);
        $result = $this->client->getResponse();
        static::assertResponseStatusCodeEquals($result, 200);
        $response = json_decode($result->getContent(), true);
        static::assertTrue($response['successful']);

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $noProductsError = $translator->trans('oro.checkout.workflow.condition.order_line_item_has_count.message');
        static::assertContains($noProductsError, $crawler->html());
    }

    public function testCheckoutErrorsOnNotAvailableShippingMethods()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        //Billing Information step
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        static::assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());

        //Shipping Information step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());

        //Shipping Method step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains(self::SHIPPING_METHOD_SIGN, $crawler->html());

        $this->disableAllShippingRules();

        //Shipping Method step with error for no shipping rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains('The selected shipping method is not available.', $crawler->html());
        static::assertContains(
            'Please return to the shipping method selection step and select a different one.',
            $crawler->html()
        );

        $this->enableAllShippingRules();

        //Payment step
        $crawler = $this->client->submit($form);
        static::assertContains(self::PAYMENT_METHOD_SIGN, $crawler->html());

        $this->disableAllShippingRules();

        //Payment step with error for no shipping rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains('The selected shipping method is not available.', $crawler->html());
        static::assertContains(
            'Please return to the shipping method selection step and select a different one.',
            $crawler->html()
        );

        $this->enableAllShippingRules();

        //Order Review step
        $crawler = $this->goToOrderReviewStepFromPaymentWithPaymentTerm($crawler); //order content has changed
        $crawler = $this->goToOrderReviewStepFromPaymentWithPaymentTerm($crawler);
        static::assertContains(self::ORDER_REVIEW_SIGN, $crawler->html());

        $this->disableAllShippingRules();

        //Order Review step with error for no shipping rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains('The selected shipping method is not available.', $crawler->html());
        static::assertContains(
            'Please return to the shipping method selection step and select a different one.',
            $crawler->html()
        );
    }

    public function testCheckoutErrorsOnNotAvailablePaymentMethods()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        //Billing Information step
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        static::assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());

        //Shipping Information step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());

        //Shipping Method step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains(self::SHIPPING_METHOD_SIGN, $crawler->html());

        //Payment step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains(self::PAYMENT_METHOD_SIGN, $crawler->html());

        $this->disableAllPaymentRules();

        //Payment step with error for no shipping rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains('The selected payment method is not available.', $crawler->html());
        static::assertContains(
            'Please return to the payment method selection step and select a different one.',
            $crawler->html()
        );

        $this->enableAllPaymentRules();

        //Order Review step
        $crawler = $this->goToOrderReviewStepFromPaymentWithPaymentTerm($crawler);
        static::assertContains(self::ORDER_REVIEW_SIGN, $crawler->html());

        $this->disableAllPaymentRules();

        //Order Review step with error for no shipping rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        static::assertContains('The selected payment method is not available.', $crawler->html());
        static::assertContains(
            'Please return to the payment method selection step and select a different one.',
            $crawler->html()
        );
    }

    private function disableAllShippingRules()
    {
        $shippingRules = $this->getAllShippingRules();

        foreach ($shippingRules as $shippingRule) {
            $shippingRule->getRule()->setEnabled(false);
        }

        $this->registry->getManager()->flush();
    }

    private function disableAllPaymentRules()
    {
        $paymentRules = $this->getAllPaymentRules();

        foreach ($paymentRules as $paymentRule) {
            $paymentRule->getRule()->setEnabled(false);
        }

        $this->registry->getManager()->flush();
    }

    private function enableAllShippingRules()
    {
        $shippingRules = $this->getAllShippingRules();

        foreach ($shippingRules as $shippingRule) {
            $shippingRule->getRule()->setEnabled(true);
        }

        $this->registry->getManager()->flush();
    }

    private function enableAllPaymentRules()
    {
        $paymentRules = $this->getAllPaymentRules();

        foreach ($paymentRules as $paymentRule) {
            $paymentRule->getRule()->setEnabled(true);
        }

        $this->registry->getManager()->flush();
    }

    /**
     * @return ShippingMethodsConfigsRule[]
     */
    private function getAllShippingRules()
    {
        return $this->registry->getRepository(ShippingMethodsConfigsRule::class)->findAll();
    }

    /**
     * @return PaymentMethodsConfigsRule[]
     */
    private function getAllPaymentRules()
    {
        return $this->registry->getRepository(PaymentMethodsConfigsRule::class)->findAll();
    }

    /**
     * {@inheritDoc}
     */
    protected function goToOrderReviewStepFromPaymentWithPaymentTerm(Crawler $crawler)
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
}
