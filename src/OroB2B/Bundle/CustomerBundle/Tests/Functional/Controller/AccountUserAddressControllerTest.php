<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use OroB2B\Bundle\CustomerBundle\Tests\Functional\Traits\AddressTestTrait;

use Symfony\Component\DomCrawler\Form;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class AccountUserAddressControllerTest extends WebTestCase
{
    use AddressTestTrait;

    /** @var AccountUser $accountUser */
    protected $accountUser;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            ]
        );

        $this->accountUser = $this->getReference(LoadAccountUserData::EMAIL);
    }

    public function testCustomerView()
    {
        $this->client->request('GET', $this->getUrl('orob2b_customer_account_user_view', [
            'id' => $this->accountUser->getId()
        ]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCustomerView
     */
    public function testCreateAddress()
    {
        $accountUser = $this->accountUser;
        $crawler  = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_customer_account_user_address_create',
                ['accountUserId' => $accountUser->getId(), '_widgetContainer' => 'dialog']
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
            $this->getUrl('orob2b_api_customer_account_user_get_accountuser_address_primary', [
                'accountUserId' => $accountUser->getId()
            ])
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

        return $accountUser->getId();
    }

    /**
     * @depends testCreateAddress
     * @param $accountUserId
     */
    public function testUpdateAddress($accountUserId)
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_customer_account_user_get_accountuser_address_primary', [
                'accountUserId' => $accountUserId
            ])
        );

        $address = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_customer_account_user_address_update',
                ['accountUserId' => $accountUserId, 'id' => $address['id'], '_widgetContainer' => 'dialog']
            )
        );

        $result = $this->client->getResponse();
        $this->assertEquals(200, $result->getStatusCode());

        /** @var Form $form */
        $form     = $crawler->selectButton('Save')->form();
        $this->fillFormForUpdateTest($form);

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_customer_account_user_get_accountuser_address_primary', [
                'accountUserId' => $accountUserId
            ])
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

        return $accountUserId;
    }

    /**
     * @depends testCreateAddress
     * @param $accountUserId
     */
    public function testDeleteAddress($accountUserId)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_api_customer_account_user_get_accountuser_address_primary',
                ['accountUserId' => $accountUserId]
            )
        );

        $address = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $crawler = $this->client->request(
            'DELETE',
            $this->getUrl(
                'orob2b_api_customer_account_user_delete_accountuser_address',
                ['accountUserId' => $accountUserId, 'addressId' => $address['id']]
            )
        );

        $result = $this->client->getResponse();
        $this->assertEquals(204, $result->getStatusCode());
    }
}
