<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

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
            ->getRepository('OroCheckoutBundle:CheckoutSource')
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
        $this->assertCount(0, $this->registry->getRepository('OroCheckoutBundle:CheckoutSource')->findAll());
        $this->assertNull(
            $this->registry->getRepository('OroShoppingListBundle:ShoppingList')->find($sourceEntityId)
        );

        /** @var EntityRepository $objectManager */
        $objectManager = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPaymentBundle:PaymentTransaction')
        ;

        $paymentTransactions = $objectManager
            ->findBy(['paymentMethod' => MoneyOrder::TYPE])
        ;

        $this->assertNotEmpty($paymentTransactions);
        $this->assertCount(1, $paymentTransactions);
        $this->assertInstanceOf('Oro\Bundle\PaymentBundle\Entity\PaymentTransaction', $paymentTransactions[0]);
    }

    private function moveToPaymentPage()
    {
        $this->startCheckout($this->getSourceEntity());
        $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $this->setAccountAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::BILLING_ADDRESS
        );
        $crawler = $this->client->submit($form);

        $form = $this->getTransitionForm($crawler);
        $this->setAccountAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::SHIPPING_ADDRESS
        );
        $crawler = $this->client->submit($form);

        $form = $this->getTransitionForm($crawler);

        $values = $this->explodeArrayPaths($form->getValues());
        $values = $this->setShippingRuleFormData($values);

        $crawler = $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $this->assertContains(self::PAYMENT_METHOD_SIGN, $crawler->html());

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
     * @return ShoppingList|object
     */
    protected function getSourceEntity()
    {
        return $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
    }

    /**
     * @param integer $addressId
     * @param Form $form
     * @param string $addressType
     */
    protected function setAccountAddress($addressId, Form $form, $addressType)
    {
        $addressId = $addressId === 0 ?: 'a_' . $addressId;

        $addressTypePath = sprintf('%s[%s][accountAddress]', self::ORO_WORKFLOW_TRANSITION, $addressType);
        $form->setValues([$addressTypePath => $addressId]);
    }
}
