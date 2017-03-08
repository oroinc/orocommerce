<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPayPalMethodsConfigsRuleData;
use Oro\Bundle\PayPalBundle\Tests\Functional\Stub\Method\ExpressCheckoutMethodStub;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadAvailableWarehouseConfig;

class CheckoutControllerTest extends CheckoutControllerTestCase
{
    /**
     * @return array
     */
    protected function getPaymentFixtures()
    {
        return [
            LoadPayPalMethodsConfigsRuleData::class,
            LoadAvailableWarehouseConfig::class
        ];
    }

    public function testCheckoutCanBeEditedAfterClosingPayPalPaymentPage()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        //Billing Information step
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $this->assertContains(self::BILLING_ADDRESS_SIGN, $crawler->html());

        //Shipping Information step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        $this->assertContains(self::SHIPPING_ADDRESS_SIGN, $crawler->html());

        //Shipping Method step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        $this->assertContains(self::SHIPPING_METHOD_SIGN, $crawler->html());

        //Payment step
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        $this->assertContains(self::PAYMENT_METHOD_SIGN, $crawler->html());

        //Order Review step
        $crawler = $this->goToOrderReviewStepFromPayment($crawler, ExpressCheckoutMethodStub::TYPE);
        $this->assertContains(self::ORDER_REVIEW_SIGN, $crawler->html());

        //Submit order
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = static::getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertArrayHasKey('purchaseRedirectUrl', $data['responseData']);
        $this->assertNotEmpty($data['responseData']['purchaseRedirectUrl']);
        $this->client->followRedirects();

        //Redirect to payment purchase url
        $this->client->request('GET', $data['responseData']['purchaseRedirectUrl']);

        //Go back to checkout
        $crawler = $this->client->request('GET', self::$checkoutUrl);

        $this->assertContains(self::EDIT_BILLING_SIGN, $crawler->html());
        $this->assertContains(self::EDIT_SHIPPING_INFO_SIGN, $crawler->html());
        $this->assertContains(self::EDIT_SHIPPING_METHOD_SIGN, $crawler->html());
        $this->assertContains(self::EDIT_PAYMENT_SIGN, $crawler->html());
    }
}
