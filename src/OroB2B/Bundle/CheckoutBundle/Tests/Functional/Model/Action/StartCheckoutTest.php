<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Model\Action;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class StartCheckoutTest extends WebTestCase
{
    /**
     * @var AccountUser
     */
    protected $user;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var StartCheckout
     */
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
        $token = new UsernamePasswordToken($this->user, false, 'key');
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->action = $this->client->getContainer()->get('orob2b_checkout.model.action.start_checkout');
    }

    public function testExecute()
    {
        $data = $this->getData();

        $this->action->initialize($data['options']);

        $this->assertCount(0, $this->getCheckouts());
        $this->assertExecution($data);
        $this->assertExecution($data);
    }

    /**
     * @param array $data
     * @param Checkout $checkout
     */
    protected function assertData(array $data, Checkout $checkout)
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $data['shoppingList'];
        $options = $data['options'];
        /** @var ActionData $context */
        $context = $data['context'];
        $checkoutSource = $checkout->getSource();

        $this->assertNotEmpty($checkoutSource);
        $this->assertNotEmpty($checkoutSource->getEntity());
        $this->assertEquals($checkoutSource->getEntity()->getId(), $shoppingList->getId());

        // Check that checkout created correctly
        $this->assertEquals($this->user->getId(), $checkout->getAccountUser()->getId());
        $this->assertEquals($this->user->getAccount()->getId(), $checkout->getAccount()->getId());
        $this->assertEquals($this->user->getAccount()->getOwner()->getId(), $checkout->getOwner()->getId());
        $this->assertEquals($checkoutSource->getId(), $checkout->getSource()->getId());

        foreach ($options[StartCheckout::CHECKOUT_DATA_KEY] as $property => $value) {
            $this->assertAttributeEquals($value, $property, $checkout);
        }

        // Check that checkout is started and step is correct
        $workflowItems = $this->getWorkflowItems($checkout);
        $this->assertCount(1, $workflowItems);

        $workflowItem = array_shift($workflowItems);
        $this->assertEquals($workflowItem->getEntity()->getId(), $checkout->getId());
        $this->assertEquals(
            $workflowItem->getDefinition()->getStartStep()->getName(),
            $workflowItem->getCurrentStep()->getName()
        );

        // Check that workflow item filled correctly
        $data = $workflowItem->getData();
        foreach ($options[StartCheckout::SETTINGS_KEY] as $key => $setting) {
            $this->assertTrue($data->has($key), sprintf('Settings key %s was not set', $key));
            $this->assertEquals($data->get($key), $setting);
        }

        // Check redirection
        $this->assertEquals(
            $context->offsetGet('redirectUrl'),
            $this->client->getContainer()->get('router')->generate(
                'orob2b_checkout_frontend_checkout',
                ['id' => $workflowItem->getId()]
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

        return [
            'shoppingList' => $shoppingList,
            'context' => $context,
            'options' => [
                StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                StartCheckout::SOURCE_ENTITY_KEY => $shoppingList,
                StartCheckout::CHECKOUT_DATA_KEY => [
                    'poNumber' => 'PO#123',
                    'currency' => 'USD'
                ],
                StartCheckout::SETTINGS_KEY => [
                    'allow_manual_source_remove' => true,
                    'disallow_billing_address_edit' => false,
                    'disallow_shipping_address_edit' => false,
                    'remove_source' => true
                ]
            ]
        ];
    }

    /**
     * @return array|Checkout[]
     */
    protected function getCheckouts()
    {
        return $this->registry->getRepository('OroB2BCheckoutBundle:Checkout')->findAll();
    }

    /**
     * @param array $data
     */
    protected function assertExecution(array $data)
    {
        $this->action->execute($data['context']);
        $checkouts = $this->getCheckouts();
        $this->assertCount(1, $checkouts);
        $this->assertData($data, $checkouts[0]);
    }

    /**
     * @param CheckoutInterface $checkout
     * @return WorkflowItem[]
     */
    protected function getWorkflowItems(CheckoutInterface $checkout)
    {
        $workflowManager = $this->getContainer()->get('oro_workflow.manager');

        return $workflowManager->getWorkflowItemsByEntity($checkout);
    }
}
