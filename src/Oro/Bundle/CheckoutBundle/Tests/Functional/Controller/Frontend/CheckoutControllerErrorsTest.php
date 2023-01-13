<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

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
use Symfony\Component\DomCrawler\Crawler;

/**
 * @dbIsolationPerTest
 * @group CommunityEdition
 * @group segfault
 */
class CheckoutControllerErrorsTest extends CheckoutControllerTestCase
{
    use EnabledPaymentMethodIdentifierTrait;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadCustomerAddresses::class,
            LoadProductUnitPrecisions::class,
            LoadShoppingListLineItems::class,
            LoadCombinedProductPrices::class,
            LoadPaymentTermData::class,
            LoadPaymentMethodsConfigsRuleData::class,
            LoadShippingMethodsConfigsRulesWithConfigs::class,
            UpdateInventoryLevelsQuantities::class
        ]);
        $this->registry = self::getContainer()->get('doctrine');

        $this->ensureSessionIsAvailable();
    }

    public function testStartCheckoutProductsWithoutPrices()
    {
        $translator = self::getContainer()->get('translator');
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $this->startCheckout($shoppingList);
        self::assertNull(self::$checkoutUrl);

        $flashBag = $this->getSession()->getFlashBag();
        $noItemsWithPriceError = $translator
            ->trans('oro.frontend.shoppinglist.messages.cannot_create_order_no_line_item_with_price');
        self::assertTrue($flashBag->has('error'));
        self::assertContains($noItemsWithPriceError, $flashBag->get('error'));
    }

    public function testStartCheckoutSeveralProductsWithoutPrices()
    {
        $translator = self::getContainer()->get('translator');
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $noProductsError = $translator
            ->trans('oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message');
        self::assertStringContainsString($noProductsError, $crawler->html());

        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::BILLING_ADDRESS);
        $crawler = $this->client->request('POST', $form->getUri(), $data);
        self::assertStringContainsString($noProductsError, $crawler->html());

        $productId = $this->getReference(LoadProductData::PRODUCT_5)->getId();
        $url = $this->getUrl('oro_shopping_list_frontend_remove_product', [
            'productId' => $productId,
            'shoppingListId' => $shoppingList->getId(),
            'lineItemId' => $this->getLineItemIdByProductId($productId, $shoppingList)
        ]);
        $this->ajaxRequest('DELETE', $url);
        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 200);
        $response = self::jsonToArray($result->getContent());
        self::assertTrue($response['successful']);

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        // because checkout line items updating on start checkout, removed product will still in the Checkout
        $noProductsError = $translator->trans(
            'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
        );
        self::assertStringContainsString($noProductsError, $crawler->html());
    }

    public function testCheckoutErrorsOnNotAvailableShippingMethods()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        //Billing Information step
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        self::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());

        //Shipping Information step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());

        //Shipping Method step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::SHIPPING_METHOD_SIGN, $crawler->html());

        $this->disableAllShippingRules();

        //Shipping Method step with error for no shipping rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString('The selected shipping method is not available.', $crawler->html());
        self::assertStringContainsString(
            'Please return to the shipping method selection step and select a different one.',
            $crawler->html()
        );

        $this->enableAllShippingRules();

        //Payment step
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::PAYMENT_METHOD_SIGN, $crawler->html());

        $this->disableAllShippingRules();

        //Payment step with error for no shipping rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString('The selected shipping method is not available.', $crawler->html());
        self::assertStringContainsString(
            'Please return to the shipping method selection step and select a different one.',
            $crawler->html()
        );

        $this->enableAllShippingRules();

        //Order Review step
        $crawler = $this->goToOrderReviewStepFromPaymentWithPaymentTerm($crawler); //order content has changed
        self::assertStringContainsString(self::ORDER_REVIEW_SIGN, $crawler->html());

        $this->disableAllShippingRules();

        //Order Review step with error for no shipping rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString('The selected shipping method is not available.', $crawler->html());
        self::assertStringContainsString(
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
        self::assertStringContainsString(self::BILLING_ADDRESS_SIGN, $crawler->html());

        //Shipping Information step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::SHIPPING_ADDRESS_SIGN, $crawler->html());

        //Shipping Method step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::SHIPPING_METHOD_SIGN, $crawler->html());

        //Payment step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString(self::PAYMENT_METHOD_SIGN, $crawler->html());

        $this->disableAllPaymentRules();

        //Payment step with error for no payment rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString('The selected payment method is not available.', $crawler->html());
        self::assertStringContainsString(
            'Please return to the payment method selection step and select a different one.',
            $crawler->html()
        );

        $this->enableAllPaymentRules();

        //Order Review step
        $crawler = $this->goToOrderReviewStepFromPaymentWithPaymentTerm($crawler);
        self::assertStringContainsString(self::ORDER_REVIEW_SIGN, $crawler->html());

        $this->disableAllPaymentRules();

        //Order Review step with error for no payment rules
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        self::assertStringContainsString('The selected payment method is not available.', $crawler->html());
        self::assertStringContainsString(
            'Please return to the payment method selection step and select a different one.',
            $crawler->html()
        );
    }

    private function disableAllShippingRules(): void
    {
        $shippingRules = $this->getAllShippingRules();

        foreach ($shippingRules as $shippingRule) {
            $shippingRule->getRule()->setEnabled(false);
        }

        $this->registry->getManager()->flush();
    }

    private function disableAllPaymentRules(): void
    {
        $paymentRules = $this->getAllPaymentRules();

        foreach ($paymentRules as $paymentRule) {
            $paymentRule->getRule()->setEnabled(false);
        }

        $this->registry->getManager()->flush();
    }

    private function enableAllShippingRules(): void
    {
        $shippingRules = $this->getAllShippingRules();

        foreach ($shippingRules as $shippingRule) {
            $shippingRule->getRule()->setEnabled(true);
        }

        $this->registry->getManager()->flush();
    }

    private function enableAllPaymentRules(): void
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
    private function getAllShippingRules(): array
    {
        return $this->registry->getRepository(ShippingMethodsConfigsRule::class)->findAll();
    }

    /**
     * @return PaymentMethodsConfigsRule[]
     */
    private function getAllPaymentRules(): array
    {
        return $this->registry->getRepository(PaymentMethodsConfigsRule::class)->findAll();
    }

    private function goToOrderReviewStepFromPaymentWithPaymentTerm(Crawler $crawler): Crawler
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

    private function getLineItemIdByProductId(int $productId, ShoppingList $shoppingList): int
    {
        $lineItems = $shoppingList->getLineItems();
        $filteredLineItems = $lineItems->filter(
            function ($lineItem) use ($productId) {
                return $lineItem->getProduct()->getId() === $productId;
            }
        );

        return $filteredLineItems->current()->getId();
    }
}
