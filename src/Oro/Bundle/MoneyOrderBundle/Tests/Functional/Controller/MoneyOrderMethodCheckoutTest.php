<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures\LoadMoneyOrderSettingsData;
use Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class MoneyOrderMethodCheckoutTest extends CheckoutControllerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getPaymentFixtures()
    {
        return LoadPaymentMethodsConfigsRuleData::class;
    }

    /**
     * @return Crawler
     */
    public function testMoneyOrderPaymentMethodExists()
    {
        $this->moveToPaymentPage();

        $crawler = $this->client->request('GET', self::$checkoutUrl);

        static::assertContains(LoadMoneyOrderSettingsData::MONEY_ORDER_LABEL, $crawler->html());
        static::assertContains(LoadMoneyOrderSettingsData::MONEY_ORDER_PAY_TO_VALUE, $crawler->html());
        static::assertContains(LoadMoneyOrderSettingsData::MONEY_ORDER_SEND_TO_VALUE, $crawler->html());

        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $values[self::ORO_WORKFLOW_TRANSITION]['payment_method'] =
            $this->getPaymentMethodIdentifier($this->getReference('money_order:channel_1'));
        $values['_widgetContainer'] = 'ajax';
        $values['_wid'] = 'ajax_checkout';

        $crawler = $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        static::assertContains(LoadMoneyOrderSettingsData::MONEY_ORDER_LABEL, $crawler->html());

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

        static::assertCount(1, $checkoutSources);
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $data = static::getJsonResponseContent($this->client->getResponse(), 200);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);

        static::assertContains(self::FINISH_SIGN, $crawler->html());
        static::assertCount(1, $this->registry->getRepository('OroCheckoutBundle:CheckoutSource')->findAll());
        static::assertNull($this->registry->getRepository('OroShoppingListBundle:ShoppingList')->find($sourceEntityId));

        /** @var EntityRepository $objectManager */
        $objectManager = static::getContainer()
            ->get('doctrine')
            ->getRepository('OroPaymentBundle:PaymentTransaction')
        ;

        $paymentTransactions = $objectManager
            ->findBy([
                'paymentMethod' => $this->getPaymentMethodIdentifier(
                    $this->getReference('money_order:channel_1')
                )
            ])
        ;

        static::assertNotEmpty($paymentTransactions);
        static::assertCount(1, $paymentTransactions);
        static::assertInstanceOf('Oro\Bundle\PaymentBundle\Entity\PaymentTransaction', $paymentTransactions[0]);
    }

    private function moveToPaymentPage()
    {
        $shoppingList = $this->getSourceEntity();
        $this->startCheckout($shoppingList);
        $this->setProductInventoryLevels($shoppingList->getLineItems()[0]);
        $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getTransitionForm($crawler);
        $this->setCustomerAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::BILLING_ADDRESS
        );
        $crawler = $this->client->submit($form);

        $form = $this->getTransitionForm($crawler);
        $this->setCustomerAddress(
            $this->getReference(self::ANOTHER_ACCOUNT_ADDRESS)->getId(),
            $form,
            self::SHIPPING_ADDRESS
        );
        $crawler = $this->client->submit($form);

        $form = $this->getTransitionForm($crawler);

        $values = $this->explodeArrayPaths($form->getValues());

        $crawler = $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        static::assertContains(self::PAYMENT_METHOD_SIGN, $crawler->html());

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getFakeForm($crawler);
        $this->client->submit($form);
    }

    /**
     * @param LineItem $lineItem
     * @return InventoryLevel
     */
    protected function setProductInventoryLevels(LineItem $lineItem)
    {
        $inventoryLevelEm = $this->registry->getManagerForClass(InventoryLevel::class);
        $productUnitPrecisionEm = $this->registry->getManagerForClass(ProductUnitPrecision::class);
        $productUnitPrecision = $productUnitPrecisionEm
            ->getRepository(ProductUnitPrecision::class)
            ->findOneBy(['product' => $lineItem->getProduct()]);
        $inventoryLevel = new InventoryLevel();
        $inventoryLevel->setProductUnitPrecision($productUnitPrecision);
        $inventoryLevel->setQuantity(10);
        $inventoryLevelEm->persist($inventoryLevel);
        $productUnitPrecisionEm->persist($productUnitPrecision);
        $inventoryLevelEm->flush();

        return $inventoryLevel;
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
    protected function setCustomerAddress($addressId, Form $form, $addressType)
    {
        $addressId = $addressId === 0 ?: 'a_' . $addressId;

        $addressTypePath = sprintf('%s[%s][customerAddress]', self::ORO_WORKFLOW_TRANSITION, $addressType);
        $form->setValues([$addressTypePath => $addressId]);
    }


    /**
     * @param Channel $channel
     * @return string
     */
    public function getPaymentMethodIdentifier(Channel $channel)
    {
        return static::getContainer()->get('oro_money_order.generator.money_order_config_identifier')
            ->generateIdentifier($channel);
    }
}
