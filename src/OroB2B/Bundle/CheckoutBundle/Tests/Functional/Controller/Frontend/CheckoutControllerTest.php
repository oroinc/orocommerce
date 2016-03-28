<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses;
use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @dbIsolation
 */
class CheckoutControllerTest extends WebTestCase
{
    /** @var  ManagerRegistry */
    protected $registry;

    /**
     * @var string
     */
    protected $checkoutUrl;

    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
            ]
        );
        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testStartCheckout()
    {
        $user = $this->registry
            ->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
        $token = new UsernamePasswordToken($user, false, 'key');
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
        $data = $this->getData();
        $action = $this->client->getContainer()->get('orob2b_checkout.model.action.start_checkout');
        $action->initialize($data['options']);
        $action->execute($data['context']);
        $this->checkoutUrl = $data['context']['redirectUrl'];

        $crawler = $this->client->request(
            'GET',
            $this->checkoutUrl
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

    }


    /**
     * @return array
     */
    protected function getData()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
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
}
