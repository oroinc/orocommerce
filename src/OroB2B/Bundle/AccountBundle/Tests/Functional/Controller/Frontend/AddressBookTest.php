<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAddressBookUserData;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as OroLoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class AddressBookTest extends WebTestCase
{
    /**
     * @var AccountUser
     */
    protected $currentUser;

    protected function setUp()
    {
//        $this->initClient(
//            [],
//            $this->generateBasicAuthHeader(OroLoadAccountUserData::AUTH_USER, OroLoadAccountUserData::AUTH_PW)
//        );
//        $this->loadFixtures([LoadAddressBookUserData::class]);
    }

    public function testIndex()
    {
        /** @var AccountUser $user */
        $this->initAddressBookClient(LoadAddressBookUserData::ACCOUNT1_USER1, LoadAddressBookUserData::ACCOUNT1_USER1);
        $user = $this->getReference(LoadAddressBookUserData::ACCOUNT1_USER1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_address_index')
        );

        $addUserAddressLink = $crawler->selectLink('Add Address')->link();
//        $addCompanyAddressLink = $crawler->selectLink('Add Company Address')->link();
//        $this->assertNotEmpty($addCompanyAddressLink);
//        $this->assertNotEmpty($addUserAddressLink);
//        $addressLists = $crawler->filter('.address-list');
//        $this->assertCount(2, $addressLists);
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
}
