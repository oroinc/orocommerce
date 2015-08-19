<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

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
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses'
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

        $this->assertOrderSave(
            $crawler,
            [
                'orob2b_order_type[owner]' => $this->getCurrentUser()->getId(),
                'orob2b_order_type[account]' => $orderAccount->getId(),
                'orob2b_order_type[poNumber]' => self::ORDER_PO_NUMBER
            ],
            [
                self::ORDER_PO_NUMBER
            ]
        );
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

        $this->assertOrderSave(
            $crawler,
            [
                'orob2b_order_type[poNumber]' => self::ORDER_PO_NUMBER_UPDATED
            ],
            [
                self::ORDER_PO_NUMBER_UPDATED
            ]
        );

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

        $this->assertOrderSave(
            $crawler,
            [
                'orob2b_order_type['. $addressType .'][accountAddress]' => 'a_'. $orderAccountAddress->getId(),
            ],
            [
                self::ORDER_PO_NUMBER_UPDATED,
                $orderAccountAddress->getPostalCode(),
                $orderAccountAddress->getStreet(),
                strtoupper($orderAccountAddress->getCity())
            ]
        );

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
        $this->assertViewPage($crawler, [self::ORDER_PO_NUMBER_UPDATED]);
    }

    /**
     * @param Crawler $crawler
     * @param array $orderData
     * @param array $expectedViewData
     */
    protected function assertOrderSave(Crawler $crawler, array $orderData, array $expectedViewData)
    {
        $form = $crawler->selectButton('Save and Close')->form($orderData);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Order has been saved', $html);
        $this->assertViewPage($crawler, $expectedViewData);
    }

    /**
     * @param Crawler $crawler
     * @param array $expectedViewData
     */
    public function assertViewPage(Crawler $crawler, array $expectedViewData)
    {
        $html = $crawler->html();
        foreach ($expectedViewData as $data) {
            $this->assertContains($data, $html);
        }
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
    }
}
