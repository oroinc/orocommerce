<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAddressBookUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AddressBookTest extends WebTestCase
{
    /**
     * @var CustomerUser
     */
    protected $currentUser;

    public function testAddressBookMenuItemHidden()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER4,
            LoadAddressBookUserData::ACCOUNT1_USER4
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_customer_user_profile')
        );
        $this->assertFalse($this->isAddressBookMenuVisible($crawler));

        $this->client->followRedirects();
        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_customer_user_address_index')
        );
        $this->assertEquals($this->client->getResponse()->getStatusCode(), Response::HTTP_FORBIDDEN);
    }

    public function testCustomerAddressView()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER1,
            LoadAddressBookUserData::ACCOUNT1_USER1
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_customer_user_address_index')
        );

        $this->assertFalse($this->isAddUserAddressButtonVisible($crawler));
        $this->assertFalse($this->isAddCustomerAddressButtonVisible($crawler));
        $this->assertFalse($this->isCustomerUserAddressSectionVisible());

        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
        $this->assertTrue($this->isCustomerAddressSectionVisible());
    }

    public function testCustomerAndCustomerUserAddressView()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER3,
            LoadAddressBookUserData::ACCOUNT1_USER3
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_customer_user_address_index')
        );
        $this->assertFalse($this->isAddUserAddressButtonVisible($crawler));
        $this->assertFalse($this->isAddCustomerAddressButtonVisible($crawler));

        $this->assertTrue($this->isCustomerUserAddressSectionVisible());
        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
        $this->assertTrue($this->isCustomerAddressSectionVisible());
    }

    public function testCustomerUserAddressView()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER2,
            LoadAddressBookUserData::ACCOUNT1_USER2
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_customer_user_address_index')
        );

        $this->assertFalse($this->isAddUserAddressButtonVisible($crawler));
        $this->assertFalse($this->isAddCustomerAddressButtonVisible($crawler));
        $this->assertFalse($this->isCustomerAddressSectionVisible());

        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
        $this->assertTrue($this->isCustomerUserAddressSectionVisible());
    }

    public function testCustomerAddressCreateButton()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER6,
            LoadAddressBookUserData::ACCOUNT1_USER6
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_customer_user_address_index')
        );

        $this->assertFalse($this->isAddUserAddressButtonVisible($crawler));
        $this->assertFalse($this->isCustomerUserAddressSectionVisible());

        $this->assertTrue($this->isCustomerAddressSectionVisible());
        $this->assertTrue($this->isAddCustomerAddressButtonVisible($crawler));
        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
    }

    public function testCustomerUserAddressCreateButton()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER7,
            LoadAddressBookUserData::ACCOUNT1_USER7
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_customer_user_address_index')
        );

        $this->assertFalse($this->isCustomerAddressSectionVisible());
        $this->assertFalse($this->isAddCustomerAddressButtonVisible($crawler));

        $this->assertTrue($this->isCustomerUserAddressSectionVisible());
        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
    }

    /**
     * @param string $username
     * @param string $password
     */
    protected function initAddressBookClient($username, $password)
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader($username, $password)
        );

        $this->loadFixtures([LoadAddressBookUserData::class]);
    }

    /**
     * @param Crawler $crawler
     * @return bool
     */
    protected function isAddUserAddressButtonVisible(Crawler $crawler)
    {
        return $crawler->selectLink('Add Address')->count() > 0;
    }

    /**
     * @param Crawler $crawler
     * @return bool
     */
    protected function isAddCustomerAddressButtonVisible(Crawler $crawler)
    {
        return $crawler->selectLink('Add Company Address')->count() > 0;
    }

    /**
     * @return bool
     */
    protected function isCustomerUserAddressSectionVisible()
    {
        return false !== strpos($this->client->getResponse(), 'My Addresses');
    }

    /**
     * @return bool
     */
    protected function isCustomerAddressSectionVisible()
    {
        return false !== strpos($this->client->getResponse(), 'Company Addresses');
    }

    /**
     * @param Crawler $crawler
     * @return bool
     */
    protected function isAddressBookMenuVisible(Crawler $crawler)
    {
        return $crawler->selectLink('Address Book')->count() > 0;
    }
}
