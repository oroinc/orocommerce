<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\Action\Action\ExtendableAction;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Checkout workflow Checkout-related actions.
 */
class CheckoutActions implements CheckoutActionsInterface
{
    public function __construct(
        private EntityAliasResolver $entityAliasResolver,
        private EntityNameResolver $entityNameResolver,
        private UrlGeneratorInterface $urlGenerator,
        private ActionExecutor $actionExecutor,
        private AddressActionsInterface $addressActions
    ) {
    }

    #[\Override]
    public function getCheckoutUrl(Checkout $checkout, ?string $transition = null): string
    {
        $parameters = ['id' => $checkout->getId()];
        if ($transition) {
            $parameters['transition'] = $transition;
        }

        return $this->urlGenerator->generate('oro_checkout_frontend_checkout', $parameters);
    }

    #[\Override]
    public function purchase(
        Checkout $checkout,
        Order $order,
        array $transactionOptions = []
    ): array {
        $successUrl = $this->getCheckoutUrl($checkout, 'finish_checkout');
        $failureUrl = $this->getCheckoutUrl($checkout, 'payment_error');
        $partiallyPaidUrl = $this->getCheckoutUrl($checkout, 'paid_partially');

        $paymentTransactionOptions = array_merge(
            [
                'successUrl' => $successUrl,
                'failureUrl' => $failureUrl,
                'partiallyPaidUrl' => $partiallyPaidUrl,
                'failedShippingAddressUrl' => $failureUrl,
                'checkoutId' => $checkout->getId()
            ],
            $transactionOptions
        );

        $result = $this->actionExecutor->executeAction(
            'payment_purchase',
            [
                'attribute' => new PropertyPath('responseData'),
                'object' => $order,
                'amount' => $order->getTotal(),
                'currency' => $order->getCurrency(),
                'paymentMethod' => $checkout->getPaymentMethod(),
                'transactionOptions' => $paymentTransactionOptions
            ]
        );

        return ['responseData' => $result->get('responseData')];
    }

    #[\Override]
    public function finishCheckout(
        Checkout $checkout,
        Order $order,
        bool $autoRemoveSource = false,
        bool $allowManualSourceRemove = false,
        bool $removeSource = false,
        bool $clearSource = false
    ): void {
        $this->addressActions->actualizeAddresses($checkout, $order);
        $this->sendConfirmationEmail($checkout, $order);
        $this->fillCheckoutCompletedData($checkout, $order);
        $this->checkoutComplete($checkout, $order);
        $this->finalizeSourceEntity(
            $checkout,
            $autoRemoveSource,
            $allowManualSourceRemove,
            $removeSource,
            $clearSource
        );
    }

    #[\Override]
    public function sendConfirmationEmail(Checkout $checkout, Order $order): void
    {
        $this->actionExecutor->executeActionGroup(
            'b2b_flow_checkout_send_order_confirmation_email',
            [
                'checkout' => $checkout,
                'order' => $order,
                'workflow' => 'b2b_flow_checkout'
            ]
        );
    }

    private function checkoutComplete(Checkout $checkout, Order $order): void
    {
        $this->actionExecutor->executeAction(
            ExtendableAction::NAME,
            [
                'events' => ['extendable_action.checkout_complete'],
                'eventData' => ['checkout' => $checkout, 'order' => $order]
            ]
        );
    }

    #[\Override]
    public function finalizeSourceEntity(
        Checkout $checkout,
        bool $autoRemoveSource = false,
        bool $allowManualSourceRemove = false,
        bool $removeSource = false,
        bool $clearSource = false
    ): void {
        if (!$autoRemoveSource && !$allowManualSourceRemove && !$removeSource && $clearSource) {
            $this->actionExecutor->executeAction('clear_checkout_source_entity', [$checkout]);
        }
        if ($autoRemoveSource || ($allowManualSourceRemove && $removeSource)) {
            $this->actionExecutor->executeAction('remove_checkout_source_entity', [$checkout]);
        }
    }

    public function fillCheckoutCompletedData(Checkout $checkout, Order $order): void
    {
        $checkout->setCompleted(true);
        $completedData = $checkout->getCompletedData();

        $completedData->offsetSet(
            'itemsCount',
            count($order->getLineItems())
        );
        $completedData->offsetSet(
            'orders',
            [
                [
                    'entityAlias' => $this->entityAliasResolver->getAlias(Order::class),
                    'entityId' => ['id' => $order->getId()]
                ]
            ]
        );
        $completedData->offsetSet(
            'currency',
            $order->getCurrency()
        );
        $completedData->offsetSet(
            'subtotal',
            $order->getSubtotalObject()->getValue()
        );
        $completedData->offsetSet(
            'total',
            $order->getTotalObject()->getValue()
        );

        if ($checkout->getSourceEntity()) {
            $completedData->offsetSet(
                'startedFrom',
                $this->entityNameResolver->getName($checkout->getSourceEntity()->getSourceDocument())
            );
        }
    }
}
