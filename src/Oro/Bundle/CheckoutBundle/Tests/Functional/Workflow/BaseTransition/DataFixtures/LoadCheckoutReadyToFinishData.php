<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Workflow\BaseTransition\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\AbstractLoadCheckoutData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Loads a checkout that has gone through "place_order" with the Payment Term method (an Order and
 * a successful PaymentTransaction exist), but has NOT run "finish_checkout" yet, so a test can flip
 * the transaction's success flag and attempt that transition itself.
 */
class LoadCheckoutReadyToFinishData extends AbstractLoadCheckoutData
{
    public const string CHECKOUT_REFERENCE = 'checkout.ready_to_finish';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadCheckout($manager, self::CHECKOUT_REFERENCE, [
            'checkout' => ['currency' => 'USD'],
            'customerUser' => 'customer_user',
            'shoppingListLineItems' => [
                ['product' => LoadProductData::PRODUCT_1],
            ],
            'billingAddress' => $this->createCheckoutAddress([
                'type' => 'billing',
                'country' => 'country_usa',
                'city' => 'Rochester',
                'region' => 'region_usa_california',
                'street' => '1215 Caldwell Road',
                'postalCode' => '14608',
                'firstName' => 'John',
                'lastName' => 'Doe'
            ]),
            'shippingAddress' => $this->createCheckoutAddress([
                'type' => 'shipping',
                'country' => 'country_usa',
                'city' => 'Romney',
                'region' => 'region_usa_florida',
                'street' => '2413 Capitol Avenue',
                'postalCode' => '47981',
                'firstName' => 'John',
                'lastName' => 'Doe'
            ])
        ]);
        $manager->flush();

        /** @var CheckoutSubtotalUpdater $checkoutSubtotalUpdater */
        $checkoutSubtotalUpdater = $this->container->get('oro_checkout.model.checkout_subtotal_updater');
        /* @var WorkflowManager $workflowManager */
        $workflowManager = $this->container->get('oro_workflow.manager');
        $transitions = [
            'continue_to_shipping_address',
            'continue_to_shipping_method',
            'continue_to_payment',
            'continue_to_order_review',
            'place_order',
        ];
        $this->initializeSecurityContext($this->getReference('customer_user'));
        /** @var Checkout $checkout */
        $checkout = $this->getReference(self::CHECKOUT_REFERENCE);
        try {
            $checkoutSubtotalUpdater->recalculateCheckoutSubtotals($checkout);
            $this->startWorkflow($workflowManager, $checkout);
            foreach ($transitions as $transition) {
                $this->transitWorkflow($workflowManager, $checkout, $transition);
            }
        } finally {
            $this->restoreSecurityContext();
        }

        $this->setReference(self::CHECKOUT_REFERENCE . '.order', $checkout->getOrder());
    }
}
