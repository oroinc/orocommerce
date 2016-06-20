<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Testing\Fixtures\LoadAccountUserData as OroLoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;

/**
 * @dbIsolation
 */
class AccountAddressControllerTest extends WebTestCase
{
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
                'orob2b_account_frontend_account_address_create',
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

        $this->assertContains('Account Address has been saved', $crawler->html());
    }

    /**
     * @param Form $form
     * @return Form
     */
    protected function fillFormForCreate(Form $form)
    {
        $form['orob2b_account_typed_address[label]'] = 'Address Label';
        $form['orob2b_account_typed_address[primary]'] = true;
        $form['orob2b_account_typed_address[namePrefix]'] = 'pref';
        $form['orob2b_account_typed_address[firstName]'] = 'first';
        $form['orob2b_account_typed_address[middleName]'] = 'middle';
        $form['orob2b_account_typed_address[lastName]'] = 'last';
        $form['orob2b_account_typed_address[nameSuffix]'] = 'suffix';
        $form['orob2b_account_typed_address[organization]'] = 'org';
        $form['orob2b_account_typed_address[phone]'] = '+05000000';
        $form['orob2b_account_typed_address[street]'] = 'Street, 1';
        $form['orob2b_account_typed_address[street2]'] = 'Street, 2';
        $form['orob2b_account_typed_address[city]'] = 'London';

        $form['orob2b_account_typed_address[postalCode]'] = 10500;

        $form['orob2b_account_typed_address[types]'] = [
            AddressType::TYPE_BILLING,
            AddressType::TYPE_SHIPPING
        ];
        $form['orob2b_account_typed_address[defaults][default]'] = [false, AddressType::TYPE_SHIPPING];

        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="orob2b_account_typed_address[country]" ' .
            'id="orob2b_account_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW">Zimbabwe</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_account_typed_address[country]'] = 'ZW';

        $doc->loadHTML(
            '<select name="orob2b_account_typed_address[region]" ' .
            'id="orob2b_account_typed_address_country_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW-MA">Manicaland</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_account_typed_address[region]'] = 'ZW-MA';

        return $form;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $address = $this->getAccountAddress();

        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', $address);

        $addressId = $address->getId();

        unset($address);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_account_frontend_account_address_update',
                ['entityId' => $this->currentUser->getId(), 'id' => $addressId]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        $form['orob2b_account_typed_address[label]'] = 'Changed Label';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Account Address has been saved', $crawler->html());

        $address = $this->getAddressById($addressId);

        $this->assertInstanceOf('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', $address);

        $this->assertEquals('Changed Label', $address->getLabel());
    }

    /**
     * @param $addressId
     * @return AccountUserAddress
     */
    protected function getAddressById($addressId)
    {
        $this->getObjectManager()->clear('OroB2BAccountBundle:AccountAddress');

        return $this->getObjectManager()
            ->getRepository('OroB2BAccountBundle:AccountAddress')
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
    protected function getAccountAddress()
    {
        return $this->getCurrentUser()->getAccount()->getAddresses()->first();
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
