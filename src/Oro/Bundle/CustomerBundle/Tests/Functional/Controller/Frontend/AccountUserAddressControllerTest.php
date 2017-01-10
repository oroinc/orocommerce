<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserAddressACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserAddressesACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

/**
 * @dbIsolation
 */
class AccountUserAddressControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadAccountUserAddressesACLData::class
            ]
        );
    }

    public function testIndex()
    {
        $this->markTestSkipped('Should be fixed after BAP-10981');
        $this->loginUser(LoadAccountUserAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_account_user_address_index')
        );

        $addCompanyAddressLink = $crawler->selectLink('Add Company Address')->link();
        $addUserAddressLink = $crawler->selectLink('Add Address')->link();
        $this->assertNotEmpty($addCompanyAddressLink);
        $this->assertNotEmpty($addUserAddressLink);
        $addressLists = $crawler->filter('.address-list');
        $this->assertCount(2, $addressLists);
    }

    public function testCreate()
    {
        $this->loginUser(LoadAccountUserAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        $user = $this->getReference(LoadAccountUserAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_frontend_account_user_address_create',
                ['entityId' => $user->getId()]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        $this->fillFormForCreate($form);

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Customer User Address has been saved', $crawler->html());
    }

    /**
     * @param Form $form
     * @return Form
     */
    protected function fillFormForCreate(Form $form)
    {
        $form['oro_account_frontend_account_user_typed_address[label]'] = 'Address Label';
        $form['oro_account_frontend_account_user_typed_address[primary]'] = true;
        $form['oro_account_frontend_account_user_typed_address[namePrefix]'] = 'pref';
        $form['oro_account_frontend_account_user_typed_address[firstName]'] = 'first';
        $form['oro_account_frontend_account_user_typed_address[middleName]'] = 'middle';
        $form['oro_account_frontend_account_user_typed_address[lastName]'] = 'last';
        $form['oro_account_frontend_account_user_typed_address[nameSuffix]'] = 'suffix';
        $form['oro_account_frontend_account_user_typed_address[organization]'] = 'org';
        $form['oro_account_frontend_account_user_typed_address[phone]'] = '+05000000';
        $form['oro_account_frontend_account_user_typed_address[street]'] = 'Street, 1';
        $form['oro_account_frontend_account_user_typed_address[street2]'] = 'Street, 2';
        $form['oro_account_frontend_account_user_typed_address[city]'] = 'London';

        $form['oro_account_frontend_account_user_typed_address[postalCode]'] = 10500;

        $form['oro_account_frontend_account_user_typed_address[types]'] = [
            AddressType::TYPE_BILLING,
            AddressType::TYPE_SHIPPING
        ];
        $form['oro_account_frontend_account_user_typed_address[defaults][default]'] = [
            false,
            AddressType::TYPE_SHIPPING
        ];

        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="oro_account_frontend_account_user_typed_address[country]" ' .
            'id="oro_account_frontend_account_user_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW">Zimbabwe</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_account_frontend_account_user_typed_address[country]'] = 'ZW';

        $selectedOwner = $this->getReference(LoadAccountUserAddressACLData::USER_ACCOUNT_2_ROLE_BASIC);
        $doc->loadHTML(
            '<select name="oro_account_frontend_account_user_typed_address[frontendOwner]" ' .
            'id="oro_account_frontend_account_user_typed_address_frontend_owner" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="' . $selectedOwner->getId() . '">CustomerUser</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);

        $form['oro_account_frontend_account_user_typed_address[frontendOwner]'] = $selectedOwner->getId();

        $doc->loadHTML(
            '<select name="oro_account_frontend_account_user_typed_address[region]" ' .
            'id="oro_account_frontend_account_user_typed_address_country_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW-MA">Manicaland</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_account_frontend_account_user_typed_address[region]'] = 'ZW-MA';

        return $form;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $this->loginUser(LoadAccountUserAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        $user = $this->getReference(LoadAccountUserAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL);
        /** @var CustomerUserAddress $address */
        $address = $user->getAddresses()->first();

        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', $address);

        $addressId = $address->getId();

        unset($address);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_frontend_account_user_address_update',
                ['entityId' => $user->getId(), 'id' => $addressId]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        $form['oro_account_frontend_account_user_typed_address[label]'] = 'Changed Label';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Customer User Address has been saved', $crawler->html());

        $address = $this->getUserAddressById($addressId);

        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', $address);

        $this->assertEquals('Changed Label', $address->getLabel());
    }

    /**
     * @param $addressId
     * @return CustomerUserAddress
     */
    protected function getUserAddressById($addressId)
    {
        $this->getObjectManager()->clear('OroCustomerBundle:CustomerUserAddress');

        return $this->getObjectManager()
            ->getRepository('OroCustomerBundle:CustomerUserAddress')
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

        /** @var CustomerAddress $address */
        $address = $resource->getAddresses()->first();

        $this->client->request(
            'GET',
            $this->getUrl(
                $route,
                ['entityId' => $resource->getId(), 'id' => $address->getId()]
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
                'route' => 'oro_customer_frontend_account_user_address_update',
                'resource' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => '',
                'status' => 401,
            ],
            'UPDATE (user from another account)' => [
                'route' => 'oro_customer_frontend_account_user_address_update',
                'resource' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'UPDATE (user from parent account : DEEP)' => [
                'route' => 'oro_customer_frontend_account_user_address_update',
                'resource' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 200,
            ],
            'UPDATE (user from parent account : LOCAL_VIEW_ONLY)' => [
                'route' => 'oro_customer_frontend_account_user_address_update',
                'resource' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 403,
            ],
            'UPDATE (user from same account : LOCAL_VIEW_ONLY)' => [
                'route' => 'oro_customer_frontend_account_user_address_update',
                'resource' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'status' => 403,
            ],
            'UPDATE (user from same account : LOCAL)' => [
                'route' => 'oro_customer_frontend_account_user_address_update',
                'resource' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL,
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
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_address_index'));
        $this->assertSame($indexResponseStatus, $this->client->getResponse()->getStatusCode());
        $response = $this->client->requestGrid(
            [
                'gridName' => 'frontend-account-account-user-address-grid',
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
            'BASIC: own orders' => [
                'user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadAccountUserAddressesACLData::ADDRESS_ACC_1_USER_BASIC
                ],
            ],
            'DEEP: all siblings and children' => [
                'user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadAccountUserAddressesACLData::ADDRESS_ACC_1_1_USER_LOCAL,
                    LoadAccountUserAddressesACLData::ADDRESS_ACC_1_USER_BASIC,
                    LoadAccountUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL,
                    LoadAccountUserAddressesACLData::ADDRESS_ACC_1_USER_DEEP,
                ],
            ],
            'LOCAL: all siblings' => [
                'user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadAccountUserAddressesACLData::ADDRESS_ACC_1_USER_BASIC,
                    LoadAccountUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL,
                    LoadAccountUserAddressesACLData::ADDRESS_ACC_1_USER_DEEP,
                ],
            ],
        ];
    }
}
