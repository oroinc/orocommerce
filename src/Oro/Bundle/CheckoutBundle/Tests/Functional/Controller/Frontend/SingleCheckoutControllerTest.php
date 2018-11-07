<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Component\DomCrawler\Form;

/**
 * @group CommunityEdition
 */
class SingleCheckoutControllerTest extends CheckoutControllerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $manager = $this->getContainer()->get('oro_workflow.manager');

        $manager->deactivateWorkflow('b2b_flow_checkout');
        $manager->activateWorkflow('b2b_flow_checkout_single_page');
    }

    public function testSaveState()
    {
        $this->startCheckout($this->getReference(LoadShoppingLists::SHOPPING_LIST_8));

        /** @var CustomerUser $customerUser */
        $customerUser = $this->client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $customer = $customerUser->getCustomer();
        $shippingAddress = $customer->getAddressByTypeName('shipping');
        $billingAddress = $customer->getAddressByTypeName('billing');

        $form = $this->getTransitionForm(
            $this->client->request('GET', self::$checkoutUrl)
        );
        $this->setAddressFields($form, 'billing_address', $billingAddress);
        $this->setAddressFields($form, 'shipping_address', $shippingAddress);


        $this->client->request(
            'POST',
            $this->getTransitionUrl('save_state'),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'responseData' => [
                    'stateSaved' => true,
                ],
            ],
            $result
        );
    }

    /**
     * @param Form $form
     * @param string $type
     * @param AbstractDefaultTypedAddress $billingAddress
     */
    protected function setAddressFields(Form $form, $type, AbstractDefaultTypedAddress $billingAddress)
    {
        $form->get("oro_workflow_transition[$type][organization]")->setValue($billingAddress->getOrganization());
        $form->get("oro_workflow_transition[$type][street]")->setValue($billingAddress->getStreet());
        $form->get("oro_workflow_transition[$type][city]")->setValue($billingAddress->getCity());
        $form->get("oro_workflow_transition[$type][country]")->setValue($billingAddress->getCountryIso2());
        $form->get("oro_workflow_transition[$type][region_text]")->setValue($billingAddress->getRegionText());
    }
}
