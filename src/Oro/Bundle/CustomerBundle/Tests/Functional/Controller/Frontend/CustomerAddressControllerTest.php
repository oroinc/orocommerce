<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddressACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddressesACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddressesACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

class CustomerAddressControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadCustomerAddressesACLData::class,
                LoadCustomerUserAddressesACLData::class
            ]
        );
    }

    public function testCreate()
    {
        $this->loginUser(LoadCustomerAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        $user = $this->getReference(LoadCustomerAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_frontend_customer_address_create',
                ['entityId' => $user->getCustomer()->getId()]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        $this->fillFormForCreate($form);

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Customer Address has been saved', $crawler->html());
    }

    /**
     * @param Form $form
     * @return Form
     */
    protected function fillFormForCreate(Form $form)
    {
        $form['oro_customer_frontend_typed_address[label]'] = 'Address Label';
        $form['oro_customer_frontend_typed_address[primary]'] = true;
        $form['oro_customer_frontend_typed_address[namePrefix]'] = 'pref';
        $form['oro_customer_frontend_typed_address[firstName]'] = 'first';
        $form['oro_customer_frontend_typed_address[middleName]'] = 'middle';
        $form['oro_customer_frontend_typed_address[lastName]'] = 'last';
        $form['oro_customer_frontend_typed_address[nameSuffix]'] = 'suffix';
        $form['oro_customer_frontend_typed_address[organization]'] = 'org';
        $form['oro_customer_frontend_typed_address[phone]'] = '+05000000';
        $form['oro_customer_frontend_typed_address[street]'] = 'Street, 1';
        $form['oro_customer_frontend_typed_address[street2]'] = 'Street, 2';
        $form['oro_customer_frontend_typed_address[city]'] = 'London';

        $form['oro_customer_frontend_typed_address[postalCode]'] = 10500;

        $form['oro_customer_frontend_typed_address[types]'] = [
            AddressType::TYPE_BILLING,
            AddressType::TYPE_SHIPPING
        ];
        $form['oro_customer_frontend_typed_address[defaults][default]'] = [false, AddressType::TYPE_SHIPPING];

        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="oro_customer_frontend_typed_address[country]" ' .
            'id="oro_customer_frontend_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW">Zimbabwe</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_customer_frontend_typed_address[country]'] = 'ZW';

        $doc->loadHTML(
            '<select name="oro_customer_frontend_typed_address[region]" ' .
            'id="oro_customer_frontend_typed_address_country_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW-MA">Manicaland</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_customer_frontend_typed_address[region]'] = 'ZW-MA';
        $user = $this->getReference(LoadCustomerAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        $doc->loadHTML(
            '<select name="oro_customer_frontend_typed_address[frontendOwner]" ' .
            'id="oro_customer_frontend_typed_address_frontend_owner" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="' . $user->getCustomer()->getId() . '">CustomerUser</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_customer_frontend_typed_address[frontendOwner]'] = $user->getCustomer()->getId();

        return $form;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $this->loginUser(LoadCustomerAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        $user = $this->getReference(LoadCustomerAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        /** @var Customer $customer */
        $customer = $user->getCustomer();
        /** @var CustomerAddress $address */
        $address = $customer->getAddresses()->first();

        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\CustomerAddress', $address);

        $addressId = $address->getId();

        unset($address);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_frontend_customer_address_update',
                ['entityId' => $customer->getId(), 'id' => $addressId]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        $form['oro_customer_frontend_typed_address[label]'] = 'Changed Label';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Customer Address has been saved', $crawler->html());

        $address = $this->getAddressById($addressId);

        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\CustomerAddress', $address);

        $this->assertEquals('Changed Label', $address->getLabel());
    }

    public function testCreatePermissionDenied()
    {
        $this->loginUser(LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY);
        $user = $this->getReference(LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY);
        $this->client->request('GET', $this->getUrl(
            'oro_customer_frontend_customer_address_create',
            ['entityId' => $user->getCustomer()->getId()]
        ));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 403);
    }

    /**
     * @param $addressId
     * @return CustomerUserAddress
     */
    protected function getAddressById($addressId)
    {
        $this->getObjectManager()->clear('OroCustomerBundle:CustomerAddress');

        return $this->getObjectManager()
            ->getRepository('OroCustomerBundle:CustomerAddress')
            ->find($addressId);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @group frontend-ACL
     * @dataProvider ACLProvider
     *
     * @param string $route
     * @param string $resource
     * @param string $user
     * @param int $status
     */
    public function testACL($route, $resource, $user, $status)
    {
        $this->loginUser($user);
        /* @var $resource CustomerUser */
        $resource = $this->getReference($resource);
        /** @var Customer $customer */
        $customer = $resource->getCustomer();
        /** @var CustomerAddress $address */
        $address = $customer->getAddresses()->first();
        $this->client->request(
            'GET',
            $this->getUrl(
                $route,
                ['entityId' => $customer->getId(), 'id' => $address->getId()]
            )
        );
        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, $status);
    }

    /**
     * @return array
     */
    public function ACLProvider()
    {
        return [
            'UPDATE (anonymous user)' => [
                'route' => 'oro_customer_frontend_customer_address_update',
                'resource' => LoadCustomerAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => '',
                'status' => 401,
            ],
            'UPDATE (user from another customer)' => [
                'route' => 'oro_customer_frontend_customer_address_update',
                'resource' => LoadCustomerAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadCustomerAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'UPDATE (user from parent customer : DEEP)' => [
                'route' => 'oro_customer_frontend_customer_address_update',
                'resource' => LoadCustomerAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 200,
            ],
            'UPDATE (user from parent customer : LOCAL_VIEW_ONLY)' => [
                'route' => 'oro_customer_frontend_customer_address_update',
                'resource' => LoadCustomerAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 403,
            ],
            'UPDATE (user from same customer : LOCAL_VIEW_ONLY)' => [
                'route' => 'oro_customer_frontend_customer_address_update',
                'resource' => LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'user' => LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'status' => 403,
            ],
            'UPDATE (user from same customer : LOCAL)' => [
                'route' => 'oro_customer_frontend_customer_address_update',
                'resource' => LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'user' => LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 200,
            ],
        ];
    }

    /**
     * @group frontend-ACL
     * @dataProvider gridACLProvider
     *
     * @param string $user
     * @param string $indexResponseStatus
     * @param string $gridResponseStatus
     * @param array $data
     */
    public function testGridACL($user, $indexResponseStatus, $gridResponseStatus, array $data = [])
    {
        $this->loginUser($user);
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_address_index'));
        $this->assertSame($indexResponseStatus, $this->client->getResponse()->getStatusCode());
        $response = $this->client->requestGrid(
            [
                'gridName' => 'frontend-customer-customer-address-grid',
            ]
        );

        self::assertResponseStatusCodeEquals($response, $gridResponseStatus);
        if (200 === $gridResponseStatus) {
            $result = self::jsonToArray($response->getContent());
            $actual = array_column($result['data'], 'id');
            $actual = array_map('intval', $actual);
            $expected = array_map(
                function ($ref) {
                    return $this->getReference($ref)->getId();
                },
                $data
            );
            sort($expected);
            sort($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function gridACLProvider()
    {
        return [
            'NOT AUTHORISED' => [
                'user' => '',
                'indexResponseStatus' => 401,
                'gridResponseStatus' => 403,
                'data' => [],
            ],
            'DEEP: all siblings and children' => [
                'user' => LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadCustomerAddressesACLData::ADDRESS_ACC_1_LEVEL_DEEP,
                    LoadCustomerAddressesACLData::ADDRESS_ACC_1_LEVEL_LOCAL,
                    LoadCustomerAddressesACLData::ADDRESS_ACC_1_1_LEVEL_LOCAL,
                    LoadCustomerAddressesACLData::ADDRESS_ACC_1_2_LEVEL_LOCAL,
                ],
            ],
            'LOCAL: all siblings' => [
                'user' => LoadCustomerAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadCustomerAddressesACLData::ADDRESS_ACC_1_LEVEL_DEEP,
                    LoadCustomerAddressesACLData::ADDRESS_ACC_1_LEVEL_LOCAL
                ],
            ],
        ];
    }
}
