<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Workflow\BaseTransition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdditionalCompletionCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCompetedCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Workflow\BaseTransition\DataFixtures\LoadCheckoutReadyToFinishData;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\FinishCheckout;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * @dbIsolationPerTest
 */
class FinishCheckoutTest extends WebTestCase
{
    private const string WORKFLOW_NAME = 'b2b_flow_checkout';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadAdditionalCompletionCheckoutData::class,
            LoadCompetedCheckoutData::class,
            LoadCheckoutReadyToFinishData::class,
        ]);
    }

    public function testFinishCheckoutServiceWiring(): void
    {
        $finishCheckout = self::getContainer()->get('oro_checkout.workflow.base_transition.finish_checkout');

        self::assertInstanceOf(FinishCheckout::class, $finishCheckout);
    }

    public function testFinishCheckoutAllowedForPaymentTerm(): void
    {
        // the fixture itself runs "place_order" and "finish_checkout" via the real workflow
        // manager while being loaded; if the new PaymentTransaction gate rejected the Payment
        // Term method's successful transaction, fixture loading in setUp() would already have
        // failed with a RuntimeException before this test method even starts.
        /** @var Checkout $checkout */
        $checkout = $this->getReference('checkout.completed');

        self::assertTrue($checkout->isCompleted());
        self::assertInstanceOf(Order::class, $checkout->getOrder());
    }

    public function testFinishCheckoutNotAllowedWhenOrderPaymentTransactionDeclined(): void
    {
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutReadyToFinishData::CHECKOUT_REFERENCE);

        $this->updateOrderPaymentTransaction(false, false);

        self::assertFalse($this->isFinishCheckoutAllowed($checkout));
        self::assertFalse($checkout->isCompleted());
    }

    public function testFinishCheckoutAllowedWhenOrderPaymentTransactionIsActiveButNotSuccessful(): void
    {
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutReadyToFinishData::CHECKOUT_REFERENCE);

        // e.g. a reservation that is activated later or a payment that awaits a confirmation
        $this->updateOrderPaymentTransaction(false, true);

        self::assertTrue($this->isFinishCheckoutAllowed($checkout));
    }

    private function updateOrderPaymentTransaction(bool $successful, bool $active): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadCheckoutReadyToFinishData::CHECKOUT_REFERENCE . '.order');

        /** @var PaymentTransactionProvider $paymentTransactionProvider */
        $paymentTransactionProvider = self::getContainer()->get('oro_payment.provider.payment_transaction');
        $paymentTransaction = $paymentTransactionProvider->getPaymentTransaction($order);
        self::assertNotNull($paymentTransaction);
        self::assertTrue($paymentTransaction->isSuccessful());

        $paymentTransaction->setSuccessful($successful);
        $paymentTransaction->setActive($active);
        $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }

    private function isFinishCheckoutAllowed(Checkout $checkout): bool
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = self::getContainer()->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow(self::WORKFLOW_NAME);
        $workflowItem = $workflowManager->getWorkflowItem($checkout, self::WORKFLOW_NAME);

        return $workflow->isTransitionAllowed($workflowItem, 'finish_checkout', new ArrayCollection());
    }
}
