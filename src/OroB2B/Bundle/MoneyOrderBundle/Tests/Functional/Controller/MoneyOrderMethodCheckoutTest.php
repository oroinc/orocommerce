<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Tests\Functional\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\MoneyOrderBundle\Method\MoneyOrder;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * @dbIsolation
 */
class MoneyOrderMethodCheckoutTest extends CheckoutControllerTestCase
{
    const MONEY_ORDER_PAY_TO_VALUE = 'Johnson Brothers LLC.';
    const MONEY_ORDER_SEND_TO_VALUE = '1234 Main St. Smallville, CA 90048';

    /** @var ConfigManager */
    protected $configManager;

    public function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getContainer()->get('oro_config.global');
        $this->configManager->set('orob2b_money_order.' . Configuration::MONEY_ORDER_ENABLED_KEY, true);
        $this->configManager->set(
            'orob2b_money_order.' . Configuration::MONEY_ORDER_PAY_TO_KEY,
            self::MONEY_ORDER_PAY_TO_VALUE
        );
        $this->configManager->set(
            'orob2b_money_order.' . Configuration::MONEY_ORDER_SEND_TO_KEY,
            self::MONEY_ORDER_SEND_TO_VALUE
        );
        $this->configManager->flush();
    }

    protected function tearDown()
    {
        $this->configManager->reload();

        parent::tearDown();
    }

    /**
     * @return Crawler
     */
    public function testMoneyOrderPaymentMethodExists()
    {
        $this->moveToPaymentPage();

        $crawler = $this->client->request('GET', self::$checkoutUrl);

        $this->assertContains(Configuration::MONEY_ORDER_LABEL, $crawler->html());
        $this->assertContains(self::MONEY_ORDER_PAY_TO_VALUE, $crawler->html());
        $this->assertContains(self::MONEY_ORDER_SEND_TO_VALUE, $crawler->html());

        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $values[self::ORO_WORKFLOW_TRANSITION]['payment_method'] = MoneyOrder::TYPE;
        $values['_widgetContainer'] = 'ajax';
        $values['_wid'] = 'ajax_checkout';

        $crawler = $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertContains(Configuration::MONEY_ORDER_LABEL, $crawler->html());

        return $crawler;
    }

    /**
     * @depends testMoneyOrderPaymentMethodExists
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

        $objectManager = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPaymentBundle:PaymentTransaction')
        ;

        $paymentTransactions = $objectManager
            ->findBy(['paymentMethod' => MoneyOrder::TYPE])
        ;

        $this->assertNotEmpty($paymentTransactions);
        $this->assertCount(1, $paymentTransactions);
        $this->assertInstanceOf('OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction', $paymentTransactions[0]);
    }

    private function moveToPaymentPage()
    {
        $this->startCheckout($this->getSourceEntity());
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->getSelectedAddressId($crawler, self::BILLING_ADDRESS);

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::BILLING_ADDRESS);
        $this->client->request('POST', $form->getUri(), $data);

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::SHIPPING_ADDRESS);
        $this->client->request('POST', $form->getUri(), $data);

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getFakeForm($crawler);
        $this->client->submit($form);
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
     * @return ShoppingList
     */
    protected function getSourceEntity()
    {
        return $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
    }
}
