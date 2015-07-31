<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class CustomerAddressControllerTest extends WebTestCase
{
    /** @var Customer $customer */
    protected $customer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
            ]
        );

        $this->customer = $this->getReference('customer.orphan');
    }

    public function testCustomerView()
    {
        $this->client->request('GET', $this->getUrl('orob2b_customer_view', ['id' => $this->customer->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCustomerView
     *
     * @return int
     */
    public function testCreateAddress()
    {
        $customer = $this->customer;
        $crawler  = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_customer_address_create',
                ['entityId' => $customer->getId(), '_widgetContainer' => 'dialog']
            )
        );

        $result = $this->client->getResponse();
        $this->assertEquals(200, $result->getStatusCode());

        /** @var Form $form */
        $form     = $crawler->selectButton('Save')->form();
        $this->fillFormForCreateTest($form);

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_customer_get_customer_address_primary', ['entityId' => $customer->getId()])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals('Badakhshān', $result['region']);
        $this->assertEquals([
            [
                'name'  => AddressType::TYPE_BILLING,
                'label' => ucfirst(AddressType::TYPE_BILLING)
            ]
        ], $result['types']);

        $this->assertEquals([
            [
                'name'  => AddressType::TYPE_BILLING,
                'label' => ucfirst(AddressType::TYPE_BILLING)
            ]
        ], $result['defaults']);

        return $customer->getId();
    }

    /**
     * @depends testCreateAddress
     *
     * @param int $id
     * @return int
     */
    public function testUpdateAddress($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_customer_get_customer_address_primary', ['entityId' => $id])
        );

        $address = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_customer_address_update',
                ['entityId' => $id, 'id' => $address['id'], '_widgetContainer' => 'dialog']
            )
        );

        $result = $this->client->getResponse();
        $this->assertEquals(200, $result->getStatusCode());

        /** @var Form $form */
        $form     = $crawler->selectButton('Save')->form();
        $form = $this->fillFormForUpdateTest($form);

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_customer_get_customer_address_primary', ['entityId' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals('Manicaland', $result['region']);
        $this->assertEquals([
            [
                'name'  => AddressType::TYPE_BILLING,
                'label' => ucfirst(AddressType::TYPE_BILLING)
            ],
            [
                'name'  => AddressType::TYPE_SHIPPING,
                'label' => ucfirst(AddressType::TYPE_SHIPPING)
            ]
        ], $result['types']);

        $this->assertEquals([
            [
                'name'  => AddressType::TYPE_SHIPPING,
                'label' => ucfirst(AddressType::TYPE_SHIPPING)
            ]
        ], $result['defaults']);

        return $id;
    }

    /**
     * @depends testCreateAddress
     *
     * @param int $customerId
     */
    public function testDeleteAddress($customerId)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_api_customer_get_customer_address_primary',
                ['entityId' => $customerId]
            )
        );

        $address = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $crawler = $this->client->request(
            'DELETE',
            $this->getUrl(
                'orob2b_api_customer_delete_customer_address',
                ['entityId' => $customerId, 'addressId' => $address['id']]
            )
        );

        $result = $this->client->getResponse();
        $this->assertEquals(204, $result->getStatusCode());
    }

    /**
     * Fill form for address tests (create test)
     *
     * @param Form $form
     * @return Form
     */
    protected function fillFormForCreateTest(Form $form)
    {
        $formNode = $form->getNode();
        $formNode->setAttribute('action', $formNode->getAttribute('action') . '?_widgetContainer=dialog');

        $form['orob2b_customer_typed_address[street]']            = 'Street';
        $form['orob2b_customer_typed_address[city]']              = 'City';
        $form['orob2b_customer_typed_address[postalCode]']        = 'Zip code';
        $form['orob2b_customer_typed_address[types]']             = [AddressType::TYPE_BILLING];
        $form['orob2b_customer_typed_address[defaults][default]'] = [AddressType::TYPE_BILLING];

        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="orob2b_customer_typed_address[country]" id="orob2b_customer_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="AF">Afghanistan</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_customer_typed_address[country]'] = 'AF';

        $doc->loadHTML(
            '<select name="orob2b_customer_typed_address[region]" id="orob2b_customer_typed_address_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="AF-BDS">Badakhshān</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_customer_typed_address[region]'] = 'AF-BDS';

        return $form;
    }

    /**
     * Fill form for address tests (update test)
     *
     * @param Form $form
     * @return Form
     */
    protected function fillFormForUpdateTest(Form $form)
    {
        $formNode = $form->getNode();
        $formNode->setAttribute('action', $formNode->getAttribute('action') . '?_widgetContainer=dialog');

        $form['orob2b_customer_typed_address[types]'] = [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING];
        $form['orob2b_customer_typed_address[defaults][default]'] = [false, AddressType::TYPE_SHIPPING];


        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="orob2b_customer_typed_address[country]" id="orob2b_customer_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW">Zimbabwe</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_customer_typed_address[country]'] = 'ZW';

        $doc->loadHTML(
            '<select name="orob2b_customer_typed_address[region]" id="orob2b_customer_typed_address_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW-MA">Manicaland</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_customer_typed_address[region]'] = 'ZW-MA';

        return $form;
    }
}
