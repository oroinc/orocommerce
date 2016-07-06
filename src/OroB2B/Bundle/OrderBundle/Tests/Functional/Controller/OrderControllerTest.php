<?php
namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\OrderBundle\Entity\OrderDiscount;
use OroB2B\Bundle\OrderBundle\Entity\Order;

/**
 * @dbIsolation
 * @group CommunityEdition
 */
class OrderControllerTest extends WebTestCase
{
    const ORDER_PO_NUMBER = 'PO_NUMBER';
    const ORDER_PO_NUMBER_UPDATED = 'NEW_PO_NUMBER';

    /**
     * @var NameFormatter
     */
    protected $formatter;

    /**
     * @var string
     */
    public static $shippingCostAmount = '999.9900';

    /**
     * @var string
     */
    public static $shippingCostCurrency = 'USD';

    /**
     * @var array
     */
    protected $disabledAddressInputs = [
        'label',
        'firstName',
        'middleName',
        'lastName',
        'nameSuffix',
        'organization',
        'street',
        'city',
        'postalCode'
    ];

    /**
     * @param Form $form
     * @param Account $orderAccount
     * @param $lineItems
     * @param $discountItems
     * @return array
     */
    public function getSubmittedData($form, $orderAccount, $lineItems, $discountItems)
    {
        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_order_type' => [
                '_token' => $form['orob2b_order_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'account' => $orderAccount->getId(),
                'poNumber' => self::ORDER_PO_NUMBER,
                'lineItems' => $lineItems,
                'discounts' => $discountItems,
            ]
        ];

        return $submittedData;
    }

    /**
     * @param Form $form
     * @param Account $orderAccount
     * @param array $lineItems
     * @param array $discountItems
     * @return array
     */
    public function getUpdatedData($form, $orderAccount, $lineItems, $discountItems)
    {
        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_order_type' => [
                '_token' => $form['orob2b_order_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'account' => $orderAccount->getId(),
                'poNumber' => self::ORDER_PO_NUMBER_UPDATED,
                'lineItems' => $lineItems,
                'discounts' => $discountItems,
            ]
        ];

        return $submittedData;
    }

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            ]
        );

        $this->formatter = $this->getContainer()->get('oro_locale.formatter.name');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('orders-grid', $crawler->html());
        $this->assertEquals('Orders', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_create'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        /** @var Account $orderAccount */
        $orderAccount = $this->getReference('account.level_1');
        
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $date = (new \DateTime('now'))->format('Y-m-d');
        $lineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 10,
                'productUnit' => 'liter',
                'price' => [
                    'value' => 100,
                    'currency' => 'USD'
                ],
                'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
                'shipBy' => $date
            ],
        ];
        $discountItems = $this->getDiscountItems();
        $submittedData = $this->getSubmittedData($form, $orderAccount, $lineItems, $discountItems);

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals(
            self::ORDER_PO_NUMBER,
            $crawler->filter('input[name="orob2b_order_type[poNumber]"]')->extract('value')[0]
        );

        $this->assertNotEquals('N/A', $crawler->filter('.user-name')->text());

        $actualLineItems = $this->getActualLineItems($crawler, count($lineItems));
        $expectedLineItems = [
            [
                'product' => $product->getId(),
                'freeFormProduct' => '',
                'quantity' => 10,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => [
                    'value' => 100,
                    'currency' => 'USD'
                ],
                'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
                'shipBy' => $date
            ]
        ];
        $this->assertEquals($expectedLineItems, $actualLineItems);

        $actualDiscountItems = $this->getActualDiscountItems($crawler, count($discountItems));
        $expectedDiscountItems = $this->getExpectedDiscountItems();
        foreach ($actualDiscountItems as $item) {
            $this->assertContains($item, $expectedDiscountItems);
        }

        /** @var Order $order */
        $order = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BOrderBundle:Order')
            ->getRepository('OroB2BOrderBundle:Order')
            ->findOneBy(['poNumber' => self::ORDER_PO_NUMBER]);
        $this->assertNotEmpty($order);

        return $order->getId();
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdateDiscountAndLineItems($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $id]));

        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        /** @var Account $orderAccount */
        $orderAccount = $this->getReference('account.level_1');

        $date = (new \DateTime('now'))->format('Y-m-d');
        $lineItems = $this->getLineItemsToUpdate($date);

        $discountItems = [
            [
                'value' => '33',
                'percent' => '33',
                'amount' => '33.33',
                'type' => OrderDiscount::TYPE_PERCENT,
                'description' => 'some test description 333'
            ],
            [
                'value' => '44.44',
                'percent' => '44',
                'amount' => '44.44',
                'type' => OrderDiscount::TYPE_AMOUNT,
                'description' => 'some other test description 444'
            ]
        ];

        $submittedData = $this->getUpdatedData($form, $orderAccount, $lineItems, $discountItems);

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check updated line items
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $id]));

        $actualLineItems = $this->getActualLineItems($crawler, count($lineItems));
        $expectedLineItems = $this->getExpectedLineItemsAfterUpdate($date);
        $this->assertEquals($expectedLineItems, $actualLineItems);

        $actualDiscountItems = $this->getActualDiscountItems($crawler, count($discountItems));
        $expectedDiscountItems = [
            [
                'value' => '33',
                'percent' => '33',
                'amount' => '33.3300',
                'type' => '%',
                'description' => 'some test description 333'
            ],
            [
                'value' => '44.4400',
                'percent' => '21.161904761905',
                'amount' => '44.4400',
                'type' => 'USD',
                'description' => 'some other test description 444'
            ]
        ];
        $this->assertEquals($expectedDiscountItems, $actualDiscountItems);
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdateBillingAddress($id)
    {
        $this->assertUpdateAddress($id, 'billingAddress');
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdateShippingAddress($id)
    {
        $this->assertUpdateAddress($id, 'shippingAddress');
    }

    /**
     * @param int $id
     * @param string $addressType
     */
    protected function assertUpdateAddress($id, $addressType)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $id]));

        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var AbstractAddress $orderAccount */
        $orderAccountAddress = $this->getReference('account.level_1.address_1');

        // Save order
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_order_type['. $addressType .'][accountAddress]' => 'a_'. $orderAccountAddress->getId(),
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Order has been saved', $html);

        $html = $crawler->html();

        $this->assertContains(self::ORDER_PO_NUMBER_UPDATED, $html);
        $this->assertContains($orderAccountAddress->getPostalCode(), $html);
        $this->assertContains($orderAccountAddress->getStreet(), $html);
        $this->assertContains(strtoupper($orderAccountAddress->getCity()), $html);

        // Check address on edit
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $id]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        // Check form values
        $formValues = $form->getValues();
        $this->assertContains(
            $orderAccountAddress->getPostalCode(),
            $formValues['orob2b_order_type['. $addressType .'][postalCode]']
        );
        $this->assertContains(
            $orderAccountAddress->getStreet(),
            $formValues['orob2b_order_type['. $addressType .'][street]']
        );
        $this->assertContains(
            $orderAccountAddress->getCity(),
            $formValues['orob2b_order_type['. $addressType .'][city]']
        );

        // Check address disabled
        foreach ($this->disabledAddressInputs as $input) {
            $crawler->filter('input[name="orob2b_order_type['. $addressType .']['. $input .']"][readonly="readonly"]');
        }
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdateShippingCost($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $id]));

        /* @var $form Form */
        $form = $crawler->selectButton('Save')->form();
        $form['orob2b_order_type[shippingCost][value]'] = self::$shippingCostAmount;
        $form['orob2b_order_type[shippingCost][currency]'] = self::$shippingCostCurrency;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $titleBlock = $crawler->filter('.responsive-section')->eq(2)->filter('.scrollspy-title')->html();
        self::assertEquals('Shipping Information', $titleBlock);

        $value  = $crawler->filter('.responsive-section')->eq(2)->filter('.controls .control-label')->html();
        self::assertEquals('$999.99', $value);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdateShippingCostEmpty($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $id]));

        /* @var $form Form */
        $form = $crawler->selectButton('Save')->form();
        $form['orob2b_order_type[shippingCost][value]'] = '';
        $form['orob2b_order_type[shippingCost][currency]'] = self::$shippingCostCurrency;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $titleBlock = $crawler->filter('.responsive-section')->eq(2)->filter('.scrollspy-title')->html();
        self::assertEquals('Shipping Information', $titleBlock);

        $value  = $crawler->filter('.responsive-section')->eq(2)->filter('.controls .control-label')->html();
        self::assertEquals('N/A', $value);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdateShippingCostZero($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $id]));

        /* @var $form Form */
        $form = $crawler->selectButton('Save')->form();
        $form['orob2b_order_type[shippingCost][value]'] = '0';
        $form['orob2b_order_type[shippingCost][currency]'] = self::$shippingCostCurrency;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $titleBlock = $crawler->filter('.responsive-section')->eq(2)->filter('.scrollspy-title')->html();
        self::assertEquals('Shipping Information', $titleBlock);

        $value  = $crawler->filter('.responsive-section')->eq(2)->filter('.controls .control-label')->html();
        self::assertEquals('$0.00', $value);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCreate
     *
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains(self::ORDER_PO_NUMBER_UPDATED, $html);
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
    }

    /**
     * @param Crawler $crawler
     * @param int $count
     * @return array
     */
    protected function getActualLineItems(Crawler $crawler, $count)
    {
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'product' => $crawler->filter('input[name="orob2b_order_type[lineItems]['. $i .'][product]"]')
                    ->extract('value')[0],
                'freeFormProduct' => $crawler
                    ->filter('input[name="orob2b_order_type[lineItems]['. $i .'][freeFormProduct]"]')
                    ->extract('value')[0],
                'quantity' => $crawler->filter('input[name="orob2b_order_type[lineItems]['. $i .'][quantity]"]')
                    ->extract('value')[0],
                'productUnit' => $crawler
                    ->filter('select[name="orob2b_order_type[lineItems]['. $i .'][productUnit]"] :selected')
                    ->html(),
                'price' => [
                    'value' => $crawler->filter('input[name="orob2b_order_type[lineItems]['. $i .'][price][value]"]')
                        ->extract('value')[0],
                    'currency' => $crawler
                        ->filter('input[name="orob2b_order_type[lineItems]['. $i .'][price][currency]"]')
                        ->extract('value')[0],
                ],
                'priceType' => $crawler
                    ->filter('input[name="orob2b_order_type[lineItems]['. $i .'][priceType]"]')
                    ->extract('value')[0],
                'shipBy' => $crawler->filter('input[name="orob2b_order_type[lineItems]['. $i .'][shipBy]"]')
                    ->extract('value')[0]
            ];
        }

        return $result;
    }

    /**
     * @param Crawler $crawler
     * @param int $count
     * @return array
     */
    protected function getActualDiscountItems(Crawler $crawler, $count)
    {
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'value' => $crawler->filter('input[name="orob2b_order_type[discounts]['. $i .'][value]"]')
                    ->extract('value')[0],
                'percent' => $crawler
                    ->filter('input[name="orob2b_order_type[discounts]['. $i .'][percent]"]')
                    ->extract('value')[0],
                'amount' => $crawler->filter('input[name="orob2b_order_type[discounts]['. $i .'][amount]"]')
                    ->extract('value')[0],
                'type' => $crawler
                    ->filter('select[name="orob2b_order_type[discounts]['. $i .'][type]"] :selected')
                    ->html(),
                'description' => $crawler->filter('input[name="orob2b_order_type[discounts]['. $i .'][description]"]')
                    ->extract('value')[0]
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getDiscountItems()
    {
        return [
            [
                'value' => '11',
                'percent' => '11',
                'amount' => '11.11',
                'type' => OrderDiscount::TYPE_PERCENT,
                'description' => 'some test description'
            ],
            [
                'value' => '22.22',
                'percent' => '22',
                'amount' => '22.22',
                'type' => OrderDiscount::TYPE_AMOUNT,
                'description' => 'some other test description'
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExpectedDiscountItems()
    {
        return [
            [
                'value' => '11',
                'percent' => '11',
                'amount' => '11.1100',
                'type' => '%',
                'description' => 'some test description'
            ],
            [
                'value' => '22.2200',
                'percent' => '2.2220',
                'amount' => '22.2200',
                'type' => 'USD',
                'description' => 'some other test description'
            ]
        ];
    }

    /**
     * @param $date
     * @return array
     */
    protected function getLineItemsToUpdate($date)
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');

        return [
            [
                'freeFormProduct' => 'Free form product',
                'quantity' => 20,
                'productUnit' => 'liter',
                'price' => [
                    'value' => 200,
                    'currency' => 'USD'
                ],
                'priceType' => OrderLineItem::PRICE_TYPE_BUNDLED,
                'shipBy' => $date
            ],
            [
                'product' => $product->getId(),
                'quantity' => 1,
                'productUnit' => 'bottle',
                'price' => [
                    'value' => 10,
                    'currency' => 'USD'
                ],
                'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
                'shipBy' => $date
            ]
        ];
    }

    /**
     * @param $date
     * @return array
     */
    protected function getExpectedLineItemsAfterUpdate($date)
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');

        return [
            [
                'product' => '',
                'freeFormProduct' => 'Free form product',
                'quantity' => 20,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => [
                    'value' => 200,
                    'currency' => 'USD'
                ],
                'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
                'shipBy' => $date
            ],
            [
                'product' => $product->getId(),
                'freeFormProduct' => '',
                'quantity' => 1,
                'productUnit' => 'orob2b.product_unit.bottle.label.full',
                'price' => [
                    'value' => 10,
                    'currency' => 'USD'
                ],
                'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
                'shipBy' => $date
            ]
        ];
    }
}
