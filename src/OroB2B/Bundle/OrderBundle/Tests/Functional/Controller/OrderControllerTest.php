<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

use DateTime;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;

/**
 * @dbIsolation
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

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits',
            ]
        );

        $this->formatter = $this->getContainer()->get('oro_locale.formatter.name');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
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

        /** @var Account $orderAccount */
        $orderAccount = $this->getReference('account.level_1');

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_order_type[owner]' => $this->getCurrentUser()->getId(),
                'orob2b_order_type[account]' => $orderAccount->getId(),
                'orob2b_order_type[poNumber]' => self::ORDER_PO_NUMBER
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Order has been saved', $html);

        $html = $crawler->html();
        $this->assertContains(self::ORDER_PO_NUMBER, $html);
    }

    /**
     * @depends testCreate
     *
     * @return int
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'orders-grid',
            [
                'orders-grid[_filter][poNumber][value]' => self::ORDER_PO_NUMBER
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id      = $result['id'];
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $result['id']]));

        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        /** @var Account $orderAccount */
        $orderAccount = $this->getReference('account.level_1');
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $date = (new DateTime('now'))->format('Y-m-d');
        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_order_type' => [
                '_token' => $form['orob2b_order_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'account' => $orderAccount->getId(),
                'poNumber' => self::ORDER_PO_NUMBER_UPDATED,
                'lineItems' => [
                    [
                        'product' => $product->getId(),
                        'freeFormProduct' => null,
                        'quantity' => 10,
                        'productUnit' => 'liter',
                        'price' => [
                            'value' => 100,
                            'currency' => 'USD'
                        ],
                        'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
                        'shipBy' => $date,
                        'comment' => 'test comment'
                    ],
                ],
            ]
        ];

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check updated order
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $id]));

        $actualLineItems = [
            [
                'product' => $crawler->filter('input[name="orob2b_order_type[lineItems][0][product]"]')
                    ->extract('value')[0],
                'quantity' => $crawler->filter('input[name="orob2b_order_type[lineItems][0][quantity]"]')
                    ->extract('value')[0],
                'productUnit' => $crawler
                    ->filter('select[name="orob2b_order_type[lineItems][0][productUnit]"] :selected')
                    ->html(),
                'price' => [
                    'value' => $crawler->filter('input[name="orob2b_order_type[lineItems][0][price][value]"]')
                        ->extract('value')[0],
                    'currency' => $crawler->filter('input[name="orob2b_order_type[lineItems][0][price][currency]"]')
                        ->extract('value')[0],
                ],
                'priceType' => $crawler->filter('select[name="orob2b_order_type[lineItems][0][priceType]"] :selected')
                    ->html(),
                'shipBy' => $crawler->filter('input[name="orob2b_order_type[lineItems][0][shipBy]"]')
                    ->extract('value')[0]
            ]
        ];

        $expectedLineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 10,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => [
                    'value' => 100,
                    'currency' => 'USD'
                ],
                'priceType' => 'per unit',
                'shipBy' => $date
            ]
        ];

        $this->assertEquals($actualLineItems, $expectedLineItems);
        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testUpdateBillingAddress($id)
    {
        $this->assertUpdateAddress($id, 'billingAddress');
    }

    /**
     * @depends testUpdate
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
     * @depends testUpdate
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
}
