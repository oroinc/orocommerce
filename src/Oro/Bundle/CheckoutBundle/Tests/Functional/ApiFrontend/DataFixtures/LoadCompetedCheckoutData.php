<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class LoadCompetedCheckoutData extends AbstractLoadCheckoutData
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadCheckout($manager, 'checkout.completed', [
            'checkout' => ['currency' => 'USD'],
            'customerUser' => 'customer_user',
            'shoppingListLineItems' => [
                ['product' => LoadProductData::PRODUCT_1],
                [
                    'product' => LoadProductKitData::PRODUCT_KIT_3,
                    'kitItems' => ['product-kit-3-kit-item-0']
                ]
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
            'finish_checkout'
        ];
        $this->initializeSecurityContext($this->getReference('customer_user'));
        /** @var Checkout $checkout */
        $checkout = $this->getReference('checkout.completed');
        try {
            $checkoutSubtotalUpdater->recalculateCheckoutSubtotals($checkout);
            $this->startWorkflow($workflowManager, $checkout);
            foreach ($transitions as $transition) {
                $this->transitWorkflow($workflowManager, $checkout, $transition);
            }
        } finally {
            $this->restoreSecurityContext();
        }
        $this->setReference(
            'checkout.completed.billing_address.customer_user_address',
            $checkout->getBillingAddress()->getCustomerUserAddress()
        );
        $this->setReference(
            'checkout.completed.shipping_address.customer_user_address',
            $checkout->getShippingAddress()->getCustomerUserAddress()
        );
        $this->setReference('checkout.completed.order', $this->getLastOrder($manager));
    }

    private function getLastOrder(ObjectManager $manager): Order
    {
        /** @var EntityManagerInterface $manager */
        return $manager->createQueryBuilder()
            ->select('e')
            ->from(Order::class, 'e')
            ->orderBy('e.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
