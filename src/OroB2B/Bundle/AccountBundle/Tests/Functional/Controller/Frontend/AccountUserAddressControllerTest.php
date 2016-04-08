<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Testing\Fixtures\LoadAccountUserData as OroLoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

class AccountUserAddressControllerTest extends WebTestCase
{
    const ADDRESS_LABEL       = 'Address Label';
    const ADDRESS_NAME_PREFIX = 'test prefix';
    const ADDRESS_COUNTRY     = 'ZW';
    const ADDRESS_REGION      = 'ZW-MA';
    const ADDRESS_FIRST_NAME  = 'First';
    const ADDRESS_MIDDLE_NAME = 'der';
    const ADDRESS_LAST_NAME   = 'Last';
    const ADDRESS_NAME_SUFFIX = 'great';
    const ADDRESS_ORG         = 'mega inc';
    const ADDRESS_PHONE       = '+5000000000';
    const ADDRESS_STREET      = 'Saint Bugs st, 42';
    const ADDRESS_STREET2     = 'Saint Bugs st, 42/2';
    const ADDRESS_CITY        = 'City';
    const ADDRESS_ZIP         = '10500';

    /**
     * @var AccountUser
     */
    protected $account;

    /**
     * @var AccountUser
     */
    protected $currentUser;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(OroLoadAccountUserData::AUTH_USER, OroLoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'
            ]
        );

        $this->currentUser = $this->getCurrentUser();
    }

    public function testCreate()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_account_frontend_account_user_address_create',
                ['entityId' => $this->currentUser->getId()]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        $this->fillFormForCreate($form);

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Account User Address has been saved', $crawler->html());
    }

    /**
     * @param Form $form
     * @return Form
     */
    protected function fillFormForCreate(Form $form)
    {
        $form['orob2b_account_account_user_typed_address[label]'] = self::ADDRESS_LABEL;
        $form['orob2b_account_account_user_typed_address[primary]'] = true;
        $form['orob2b_account_account_user_typed_address[namePrefix]'] = self::ADDRESS_NAME_PREFIX;
        $form['orob2b_account_account_user_typed_address[firstName]'] = self::ADDRESS_FIRST_NAME;
        $form['orob2b_account_account_user_typed_address[middleName]'] = self::ADDRESS_MIDDLE_NAME;
        $form['orob2b_account_account_user_typed_address[lastName]'] = self::ADDRESS_LAST_NAME;
        $form['orob2b_account_account_user_typed_address[nameSuffix]'] = self::ADDRESS_NAME_SUFFIX;
        $form['orob2b_account_account_user_typed_address[organization]'] = self::ADDRESS_ORG;
        $form['orob2b_account_account_user_typed_address[phone]'] = self::ADDRESS_PHONE;
        $form['orob2b_account_account_user_typed_address[street]'] = self::ADDRESS_STREET;
        $form['orob2b_account_account_user_typed_address[street2]'] = self::ADDRESS_STREET2;
        $form['orob2b_account_account_user_typed_address[city]'] = self::ADDRESS_CITY;

        $form['orob2b_account_account_user_typed_address[postalCode]'] = self::ADDRESS_ZIP;

        $form['orob2b_account_account_user_typed_address[types]'] = [
            AddressType::TYPE_BILLING,
            AddressType::TYPE_SHIPPING
        ];
        $form['orob2b_account_account_user_typed_address[defaults][default]'] = [false, AddressType::TYPE_SHIPPING];

        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="orob2b_account_account_user_typed_address[country]" ' .
            'id="orob2b_account_account_user_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW">Zimbabwe</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_account_account_user_typed_address[country]'] = 'ZW';

        $doc->loadHTML(
            '<select name="orob2b_account_account_user_typed_address[region]" ' .
            'id="orob2b_account_account_user_typed_address_country_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW-MA">Manicaland</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_account_account_user_typed_address[region]'] = self::ADDRESS_REGION;

        return $form;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $address = $this->getUserAddress();

        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', $address);

        $addressId = $address->getId();

        unset($address);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_account_frontend_account_user_address_update',
                ['entityId' => $this->currentUser->getId(), 'id' => $addressId]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        $form['orob2b_account_account_user_typed_address[label]'] = 'Changed Label';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Account User Address has been saved', $crawler->html());

        $address = $this->getUserAddressById($addressId);

        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', $address);

        $this->assertEquals('Changed Label', $address->getLabel());
    }

    /**
     * @param $addressId
     * @return AccountUserAddress
     */
    protected function getUserAddressById($addressId)
    {
        $this->getObjectManager()->clear('OroB2BAccountBundle:AccountUserAddress');

        return $this->getObjectManager()
            ->getRepository('OroB2BAccountBundle:AccountUserAddress')
            ->find($addressId);
    }

    /**
     * @return AccountUser
     */
    protected function getCurrentUser()
    {
        return $this->getUserRepository()->findOneBy(['username' => OroLoadAccountUserData::AUTH_USER]);
    }

    /**
     * @return mixed|AccountUserAddress
     */
    protected function getUserAddress()
    {
        return $this->getCurrentUser()->getAddresses()->first();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BAccountBundle:AccountUser');
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
