<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAddressBookUserData;

/**
 * @dbIsolation
 */
class AddressBookTest extends WebTestCase
{
    /**
     * @var AccountUser
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
            $this->getUrl('oro_account_frontend_account_user_profile')
        );
        $this->assertFalse($this->isAddressBookMenuVisible($crawler));

        $this->client->followRedirects();
        $this->client->request(
            'GET',
            $this->getUrl('oro_account_frontend_account_user_address_index')
        );
        $this->assertEquals($this->client->getResponse()->getStatusCode(), Response::HTTP_FORBIDDEN);
    }

    public function testAccountAddressView()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER1,
            LoadAddressBookUserData::ACCOUNT1_USER1
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_account_frontend_account_user_address_index')
        );

        $this->assertFalse($this->isAddUserAddressButtonVisible($crawler));
        $this->assertFalse($this->isAddAccountAddressButtonVisible($crawler));
        $this->assertFalse($this->isAccountUserAddressSectionVisible());

        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
        $this->assertTrue($this->isAccountAddressSectionVisible());
    }

    public function testAccountAndAccountUserAddressView()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER3,
            LoadAddressBookUserData::ACCOUNT1_USER3
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_account_frontend_account_user_address_index')
        );
        $this->assertFalse($this->isAddUserAddressButtonVisible($crawler));
        $this->assertFalse($this->isAddAccountAddressButtonVisible($crawler));

        $this->assertTrue($this->isAccountUserAddressSectionVisible());
        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
        $this->assertTrue($this->isAccountAddressSectionVisible());
    }

    public function testAccountUserAddressView()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER2,
            LoadAddressBookUserData::ACCOUNT1_USER2
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_account_frontend_account_user_address_index')
        );

        $this->assertFalse($this->isAddUserAddressButtonVisible($crawler));
        $this->assertFalse($this->isAddAccountAddressButtonVisible($crawler));
        $this->assertFalse($this->isAccountAddressSectionVisible());

        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
        $this->assertTrue($this->isAccountUserAddressSectionVisible());
    }

    public function testAccountAddressCreateButton()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER6,
            LoadAddressBookUserData::ACCOUNT1_USER6
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_account_frontend_account_user_address_index')
        );

        $this->assertFalse($this->isAddUserAddressButtonVisible($crawler));
        $this->assertFalse($this->isAccountUserAddressSectionVisible());

        $this->assertTrue($this->isAccountAddressSectionVisible());
        $this->assertTrue($this->isAddAccountAddressButtonVisible($crawler));
        $this->assertTrue($this->isAddressBookMenuVisible($crawler));
    }

    public function testAccountUserAddressCreateButton()
    {
        $this->initAddressBookClient(
            LoadAddressBookUserData::ACCOUNT1_USER7,
            LoadAddressBookUserData::ACCOUNT1_USER7
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_account_frontend_account_user_address_index')
        );

        $this->assertFalse($this->isAccountAddressSectionVisible());
        $this->assertFalse($this->isAddAccountAddressButtonVisible($crawler));

        $this->assertTrue($this->isAddUserAddressButtonVisible($crawler));
        $this->assertTrue($this->isAccountUserAddressSectionVisible());
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
    protected function isAddAccountAddressButtonVisible(Crawler $crawler)
    {
        return $crawler->selectLink('Add Company Address')->count() > 0;
    }

    /**
     * @return bool
     */
    protected function isAccountUserAddressSectionVisible()
    {
        return false !== strpos($this->client->getResponse(), 'My Addresses');
    }

    /**
     * @return bool
     */
    protected function isAccountAddressSectionVisible()
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
