<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Model\Action;

use Escape\WSSEAuthenticationBundle\Security\Core\Authentication\Token\Token;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @dbIsolation
 */
class StartCheckoutTest extends WebTestCase
{
    /** @var  AccountUser */
    protected $user;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var  StartCheckout */
    protected $action;

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
            ]
        );
        $this->registry = $this->getContainer()->get('doctrine');
        $this->user = $this->registry
            ->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
        $token = new Token();
        $token->setUser($this->user);
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
        $this->action = $this->client->getContainer()->get('orob2b_checkout.model.action.start_checkout');
    }

    public function testFirstExecute()
    {
        $checkouts = $this->registry->getRepository('OroB2BCheckoutBundle:Checkout')->findAll();
        $checkoutSources = $this->registry->getRepository('OroB2BCheckoutBundle:CheckoutSource')->findAll();
        $this->assertCount(0, $checkouts);
        $this->assertCount(0, $checkoutSources);
        $data = $this->getData();
        $this->action->initialize($data['options']);
        $this->action->execute($data['context']);
        $this->checkData($data['shoppingList'], $data['poNumber'], $data['settings'], $data['context']);
    }

    public function testSecondExecute()
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get('doctrine');
        $checkouts = $registry->getRepository('OroB2BCheckoutBundle:Checkout')->findAll();
        $checkoutSources = $registry->getRepository('OroB2BCheckoutBundle:CheckoutSource')->findAll();
        $this->assertCount(1, $checkouts);
        $this->assertCount(1, $checkoutSources);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->action->initialize(
            [
                StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                StartCheckout::SOURCE_ENTITY_KEY => $shoppingList
            ]
        );
        $this->action->execute(new ActionData(['data' => $shoppingList]));
        $data = $this->getData();
        $this->checkData($data['shoppingList'], $data['poNumber'], $data['settings'], $data['context']);
    }

    /**
     * @param $shoppingList
     * @param $poNumber
     * @param $settings
     * @param $context
     */
    protected function checkData(
        ShoppingList $shoppingList,
        $poNumber,
        array $settings,
        ActionData $context
    ) {
        $checkouts = $this->registry->getRepository('OroB2BCheckoutBundle:Checkout')->findAll();
        $checkoutSources = $this->registry->getRepository('OroB2BCheckoutBundle:CheckoutSource')->findAll();
        $this->assertCount(1, $checkouts);
        $this->assertCount(1, $checkoutSources);
        $checkout = $checkouts[0];
        $checkoutSource = $checkoutSources[0];
        $this->assertEquals(spl_object_hash($checkoutSource->getEntity()), spl_object_hash($shoppingList));
        $this->assertEquals(spl_object_hash($this->user), spl_object_hash($checkout->getAccountUser()));
        $this->assertEquals(spl_object_hash($this->user->getAccount()), spl_object_hash($checkout->getAccount()));
        $this->assertEquals('USD', $checkout->getCurrency());
        $this->assertEquals(
            spl_object_hash($this->user->getAccount()->getOwner()),
            spl_object_hash($checkout->getOwner())
        );
        $this->assertEquals($poNumber, $checkout->getPoNumber());
        $this->assertEquals(spl_object_hash($checkoutSource), spl_object_hash($checkout->getSource()));
        $workflowItem = $checkout->getWorkflowItem();
        $this->assertEquals(spl_object_hash($workflowItem->getEntity()), spl_object_hash($checkout));
        $this->assertEquals(1, $workflowItem->getCurrentStep()->getId());
        $currentStep = $workflowItem->getCurrentStep();
        $this->assertEquals(
            'orob2b.checkout.workflow.b2b_flow_checkout.step.enter_billing_address.label',
            $currentStep->getLabel()
        );
        $this->assertEquals(
            'enter_billing_address',
            $currentStep->getName()
        );
        $data = $workflowItem->getData();
        foreach ($settings as $key => $setting) {
            $this->assertTrue($data->has($key));
            $this->assertEquals($data->get($key), $setting);
        }
        $this->assertEquals(spl_object_hash($checkout), spl_object_hash($data->get('checkout')));
        $this->assertEquals($workflowItem->getSerializedData(), json_encode($settings));
        $this->assertEquals($workflowItem->getEntityId(), $checkout->getId());
        $this->assertEquals(
            $context->offsetGet('redirectUrl'),
            $this->client->getContainer()->get('router')->generate(
                'orob2b_checkout_frontend_checkout',
                ['id' => $checkout->getId()]
            )
        );
    }

    /**
     * @return array
     */
    protected function getData()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $context = new ActionData(['data' => $shoppingList]);

        $poNumber = 123;
        $settings = [
            'allow_source_remove' => true,
            'disallow_billing_address_edit' => false,
            'disallow_shipping_address_edit' => false,
            'remove_source' => true
        ];
        $options = [
            StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
            StartCheckout::SOURCE_ENTITY_KEY => $shoppingList,
            StartCheckout::CHECKOUT_DATA_KEY => [
                'poNumber' => $poNumber
            ],
            StartCheckout::SETTINGS_KEY => $settings
        ];

        return [
            'shoppingList' => $shoppingList,
            'context' => $context,
            'poNumber' => $poNumber,
            'settings' => $settings,
            'options' => $options
        ];
    }
}
