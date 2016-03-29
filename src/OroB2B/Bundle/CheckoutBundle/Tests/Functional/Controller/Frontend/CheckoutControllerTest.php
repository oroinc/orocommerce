<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @dbIsolation
 */
class CheckoutControllerTest extends WebTestCase
{
    const MANUAL_ADDRESS = 0;
    const FIRST_NAME = 'Jackie';
    const LAST_NAME = 'Chuck';
    const STREET = 'Fake Street';
    const POSTAL_CODE = '123456';
    const COUNTRY = 'UA';
    const REGION = 'UA-65';
    const ORO_WORKFLOW_TRANSITION = 'oro_workflow_transition';

    /** @var  ManagerRegistry */
    protected $registry;


    /**
     * @var string
     */
    protected static $checkoutUrl;

    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
            ]
        );
        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testStartCheckout()
    {
        $user = $this->registry
            ->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
        $user->setAccount($this->getReference('account.level_1'));
        $token = new UsernamePasswordToken($user, false, 'key');
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
        $data = $this->getData();
        $action = $this->client->getContainer()->get('orob2b_checkout.model.action.start_checkout');
        $action->initialize($data['options']);
        $action->execute($data['context']);
        self::$checkoutUrl = $data['context']['redirectUrl'];
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $selectedAddress = $this->getSelectedAddress($crawler, 'billing_address');
        $this->assertContains('BILLING ADDRESS FROM YOUR ACCOUNT', $crawler->html());
        $this->assertEquals($selectedAddress->getId(), $this->getReference('account.level_1.address_2')->getId());
    }

    /**
     * @depends testStartCheckout
     */
    public function testSubmitBillingOnManuallyValidation()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $crawler->filter('form[name=oro_workflow_transition]')->form();
        $form['oro_workflow_transition[billing_address][accountAddress]'] = self::MANUAL_ADDRESS;
        $crawler = $this->client->submit($form);
        $this->assertContains('BILLING ADDRESS FROM YOUR ACCOUNT', $crawler->html());
        $invalidFields = [
            'oro_workflow_transition[billing_address][firstName]',
            'oro_workflow_transition[billing_address][lastName]',
            'oro_workflow_transition[billing_address][street]',
            'oro_workflow_transition[billing_address][postalCode]',
        ];
        $this->checkValidationErrors($invalidFields, $crawler);
    }

    /**
     * @depends testSubmitBillingOnManuallyValidation
     */
    public function testSubmitBillingOnManually()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $form = $crawler->filter('form[name=oro_workflow_transition]')->form();
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, 'billing_address');
        $crawler = $this->client->request('POST', $form->getUri(), $data);
        $this->assertContains('SHIPPING ADDRESS FROM YOUR ACCOUNT', $crawler->html());
    }

    /**
     * @param array $formFields
     * @param Crawler $crawler
     */
    protected function checkValidationErrors(array $formFields, Crawler $crawler)
    {
        foreach ($formFields as $formField) {
            $fieldData = $crawler->filter(sprintf('input[name="%s"]', $formField))->parents()->parents()->html();
            $this->assertContains('This value should not be blank.', $fieldData);
        }
    }

    /**
     * @param Crawler $crawler
     * @param string $type
     * @return null|AccountAddress
     */
    protected function getSelectedAddress(Crawler $crawler, $type)
    {
        $select = $crawler->filter(
            sprintf('select[name="oro_workflow_transition[%s][accountAddress]"]', $type)
        );
        if ($select->filter('option')->count() == 1) {
            return null;
        } else {
            $value = $select->filter('optgroup')->filter('option[selected="selected"]')->attr('value');
            $id = (int)substr($value, strpos($value, '_') + 1);

            return $this->registry->getRepository('OroB2BAccountBundle:AccountAddress')->find($id);
        }
    }

    /**
     * @param Account $account
     */
    protected function setCurrentAccountOnAddresses(Account $account)
    {
        $addresses = $this->registry->getRepository('OroB2BAccountBundle:AccountAddress')->findAll();
        foreach ($addresses as $address) {
            $address->setFrontendOwner($account);
        }
        $this->registry->getManager()->flush();
    }

    /**
     * @param array $values
     * @param string $type
     * @return array
     */
    protected function setFormData(array $values, $type)
    {
        $address = [
            'accountAddress' => self::MANUAL_ADDRESS,
            'firstName' => self::FIRST_NAME,
            'lastName' => self::LAST_NAME,
            'street' => self::STREET,
            'postalCode' => self::POSTAL_CODE,
            'country' => self::COUNTRY,
            'region' => self::REGION,
        ];
        $values[self::ORO_WORKFLOW_TRANSITION][$type] = array_merge(
            $values[self::ORO_WORKFLOW_TRANSITION][$type],
            $address
        );

        return $values;
    }

    /**
     * @return array
     */
    protected function getData()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $context = new ActionData(['data' => $shoppingList]);

        return [
            'shoppingList' => $shoppingList,
            'context' => $context,
            'options' => [
                StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                StartCheckout::SOURCE_ENTITY_KEY => $shoppingList,
                StartCheckout::CHECKOUT_DATA_KEY => [
                    'poNumber' => 'PO#123',
                    'currency' => 'EUR'
                ],
                StartCheckout::SETTINGS_KEY => [
                    'allow_source_remove' => true,
                    'disallow_billing_address_edit' => false,
                    'disallow_shipping_address_edit' => false,
                    'remove_source' => true
                ]
            ]
        ];
    }

    /**
     * @param array $values
     * @return array
     */
    protected function explodeArrayPaths($values)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $parameters = [];
        foreach ($values as $key => $val) {
            if (!$pos = strpos($key, '[')) {
                continue;
            }
            $key = '[' . substr($key, 0, $pos) . ']' . substr($key, $pos);
            $accessor->setValue($parameters, $key, $val);
        }

        return $parameters;
    }
}
