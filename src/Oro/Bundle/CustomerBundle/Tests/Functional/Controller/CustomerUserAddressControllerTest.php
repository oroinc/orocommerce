<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;

/**
 * @dbIsolation
 */
class CustomerUserAddressControllerTest extends WebTestCase
{
    /** @var CustomerUser $customerUser */
    protected $customerUser;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader()));

        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData',
            ]
        );

        $this->customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
    }

    public function testCustomerUserView()
    {
        $this->client->request('GET', $this->getUrl('oro_customer_customer_user_view', [
            'id' => $this->customerUser->getId()
        ]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertContains('Address Book', $content);
    }

    /**
     * @depends testCustomerUserView
     * @return int
     */
    public function testCreateAddress()
    {
        $customerUser = $this->customerUser;
        $crawler     = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_address_create',
                ['entityId' => $customerUser->getId(), '_widgetContainer' => 'dialog']
            )
        );

        $result = $this->client->getResponse();
        $this->assertEquals(200, $result->getStatusCode());

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $this->fillFormForCreateTest($form);

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_customer_get_customeruser_address_primary', [
                'entityId' => $customerUser->getId()
            ]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        sort($result['types']);

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

        return $customerUser->getId();
    }

    /**
     * @depends testCreateAddress
     *
     * @param int $customerUserId
     * @return int
     */
    public function testUpdateAddress($customerUserId)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_customer_get_customeruser_address_primary', [
                'entityId' => $customerUserId
            ]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $address = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_address_update',
                ['entityId' => $customerUserId, 'id' => $address['id'], '_widgetContainer' => 'dialog']
            )
        );

        $result = $this->client->getResponse();
        $this->assertEquals(200, $result->getStatusCode());

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $this->fillFormForUpdateTest($form);

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_customer_get_customeruser_address_primary', [
                'entityId' => $customerUserId
            ]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        sort($result['types']);

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

        return $customerUserId;
    }

    /**
     * @depends testCreateAddress
     *
     * @param int $customerUserId
     */
    public function testDeleteAddress($customerUserId)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_customer_get_customeruser_address_primary',
                ['entityId' => $customerUserId]
            ),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $address = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_customer_delete_customeruser_address',
                ['entityId' => $customerUserId, 'addressId' => $address['id']]
            ),
            [],
            [],
            $this->generateWsseAuthHeader()
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

        $form['oro_customer_customer_user_typed_address[street]']            = 'Street';
        $form['oro_customer_customer_user_typed_address[city]']              = 'City';
        $form['oro_customer_customer_user_typed_address[postalCode]']        = 'Zip code';
        $form['oro_customer_customer_user_typed_address[types]']             = [AddressType::TYPE_BILLING];
        $form['oro_customer_customer_user_typed_address[defaults][default]'] = [AddressType::TYPE_BILLING];

        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="oro_customer_customer_user_typed_address[country]" ' .
            'id="oro_customer_customer_user_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="AF">Afghanistan</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_customer_customer_user_typed_address[country]'] = 'AF';

        $doc->loadHTML(
            '<select name="oro_customer_customer_user_typed_address[region]" ' .
            'id="oro_customer_customer_user_typed_address_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="AF-BDS">Badakhshān</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_customer_customer_user_typed_address[region]'] = 'AF-BDS';

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

        $form['oro_customer_customer_user_typed_address[types]'] = [
            AddressType::TYPE_BILLING,
            AddressType::TYPE_SHIPPING
        ];
        $form['oro_customer_customer_user_typed_address[defaults][default]'] = [false, AddressType::TYPE_SHIPPING];


        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="oro_customer_customer_user_typed_address[country]" ' .
            'id="oro_customer_customer_user_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW">Zimbabwe</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_customer_customer_user_typed_address[country]'] = 'ZW';

        $doc->loadHTML(
            '<select name="oro_customer_customer_user_typed_address[region]" ' .
            'id="oro_customer_customer_user_typed_address_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW-MA">Manicaland</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_customer_customer_user_typed_address[region]'] = 'ZW-MA';

        return $form;
    }
}
