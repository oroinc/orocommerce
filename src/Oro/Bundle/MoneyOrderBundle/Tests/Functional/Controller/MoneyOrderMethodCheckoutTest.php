<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures\LoadMoneyOrderSettingsData;
use Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * @group CommunityEdition
 */
class MoneyOrderMethodCheckoutTest extends CheckoutControllerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getPaymentFixtures(): array
    {
        return [
            LoadPaymentMethodsConfigsRuleData::class
        ];
    }

    public function testMoneyOrderPaymentMethodExists(): Crawler
    {
        $this->moveToPaymentPage();

        $crawler = $this->client->request('GET', self::$checkoutUrl);

        self::assertStringContainsString(LoadMoneyOrderSettingsData::MONEY_ORDER_LABEL, $crawler->html());
        self::assertStringContainsString(LoadMoneyOrderSettingsData::MONEY_ORDER_PAY_TO_VALUE, $crawler->html());
        self::assertStringContainsString(LoadMoneyOrderSettingsData::MONEY_ORDER_SEND_TO_VALUE, $crawler->html());

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

        self::assertStringContainsString(LoadMoneyOrderSettingsData::MONEY_ORDER_LABEL, $crawler->html());

        return $crawler;
    }

    /**
     * @depends testMoneyOrderPaymentMethodExists
     */
    public function testSubmitOrder(Crawler $crawler)
    {
        $sourceEntity = $this->getSourceEntity();
        $sourceEntityId = $sourceEntity->getId();
        $checkoutSources = $this->registry
            ->getRepository(CheckoutSource::class)
            ->findBy(['shoppingList' => $sourceEntity]);

        self::assertCount(1, $checkoutSources);
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $data = self::getJsonResponseContent($this->client->getResponse(), 200);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);

        self::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        self::assertCount(1, $this->registry->getRepository(CheckoutSource::class)->findAll());
        self::assertNull($this->registry->getRepository(ShoppingList::class)->find($sourceEntityId));

        /** @var EntityRepository $objectManager */
        $objectManager = self::getContainer()->get('doctrine')
            ->getRepository(PaymentTransaction::class);

        $paymentTransactions = $objectManager
            ->findBy([
                'paymentMethod' => $this->getPaymentMethodIdentifier(
                    $this->getReference('money_order:channel_1')
                )
            ]);

        self::assertNotEmpty($paymentTransactions);
        self::assertCount(1, $paymentTransactions);
        self::assertInstanceOf(PaymentTransaction::class, $paymentTransactions[0]);
    }

    private function moveToPaymentPage()
    {
        $shoppingList = $this->getSourceEntity();
        $this->startCheckout($shoppingList);
        $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

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

        self::assertStringContainsString(self::PAYMENT_METHOD_SIGN, $crawler->html());

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $this->getFakeForm($crawler);
        $this->client->submit($form);
    }

    private function getFakeForm(Crawler $crawler): Form
    {
        return $crawler->filter('form')->form();
    }

    private function getSourceEntity(): ShoppingList
    {
        return $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
    }

    private function setCustomerAddress(int $addressId, Form $form, string $addressType): void
    {
        $addressId = $addressId === 0 ?: 'a_' . $addressId;

        $addressTypePath = sprintf('%s[%s][customerAddress]', self::ORO_WORKFLOW_TRANSITION, $addressType);
        $form->setValues([$addressTypePath => $addressId]);
    }

    private function getPaymentMethodIdentifier(Channel $channel): string
    {
        return self::getContainer()->get('oro_money_order.generator.money_order_config_identifier')
            ->generateIdentifier($channel);
    }
}
