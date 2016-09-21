<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as OroLoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;

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
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts'
            ]
        );

        $this->currentUser = $this->getCurrentUser();
    }

    public function testCreate()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_account_frontend_account_address_create',
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
        $form['oro_account_typed_address[label]'] = 'Address Label';
        $form['oro_account_typed_address[primary]'] = true;
        $form['oro_account_typed_address[namePrefix]'] = 'pref';
        $form['oro_account_typed_address[firstName]'] = 'first';
        $form['oro_account_typed_address[middleName]'] = 'middle';
        $form['oro_account_typed_address[lastName]'] = 'last';
        $form['oro_account_typed_address[nameSuffix]'] = 'suffix';
        $form['oro_account_typed_address[organization]'] = 'org';
        $form['oro_account_typed_address[phone]'] = '+05000000';
        $form['oro_account_typed_address[street]'] = 'Street, 1';
        $form['oro_account_typed_address[street2]'] = 'Street, 2';
        $form['oro_account_typed_address[city]'] = 'London';

        $form['oro_account_typed_address[postalCode]'] = 10500;

        $form['oro_account_typed_address[types]'] = [
            AddressType::TYPE_BILLING,
            AddressType::TYPE_SHIPPING
        ];
        $form['oro_account_typed_address[defaults][default]'] = [false, AddressType::TYPE_SHIPPING];

        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="oro_account_typed_address[country]" ' .
            'id="oro_account_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW">Zimbabwe</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_account_typed_address[country]'] = 'ZW';

        $doc->loadHTML(
            '<select name="oro_account_typed_address[region]" ' .
            'id="oro_account_typed_address_country_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW-MA">Manicaland</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['oro_account_typed_address[region]'] = 'ZW-MA';

        return $form;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $address = $this->getAccountAddress();

        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\AccountAddress', $address);

        $addressId = $address->getId();

        unset($address);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_account_frontend_account_address_update',
                ['entityId' => $this->currentUser->getId(), 'id' => $addressId]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        $form['oro_account_typed_address[label]'] = 'Changed Label';

        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Account Address has been saved', $crawler->html());

        $address = $this->getAddressById($addressId);

        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\AccountAddress', $address);

        $this->assertEquals('Changed Label', $address->getLabel());
    }

    /**
     * @param $addressId
     * @return AccountUserAddress
     */
    protected function getAddressById($addressId)
    {
        $this->getObjectManager()->clear('OroCustomerBundle:AccountAddress');

        return $this->getObjectManager()
            ->getRepository('OroCustomerBundle:AccountAddress')
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
        return $this->getObjectManager()->getRepository('OroCustomerBundle:AccountUser');
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
