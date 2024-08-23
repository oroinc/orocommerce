<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;

/**
 * Checkout workflow Order-related actions.
 */
class OrderActions implements OrderActionsInterface
{
    private const ORDER_CONFIRMATION_EMAIL_TEMPLATE = 'order_confirmation_email';

    private int $immediateEmailLineItemsLimit = 10;

    public function __construct(
        private AddressActions $addressActions,
        private PaymentTermProviderInterface $paymentTermProvider,
        private CheckoutLineItemsManager $checkoutLineItemsManager,
        private MapperInterface $mapper,
        private EntityPaymentMethodsProvider $paymentMethodsProvider,
        private TotalHelper $totalHelper,
        private ActionExecutor $actionExecutor
    ) {
    }

    public function setImmediateEmailLineItemsLimit(int $limit): void
    {
        $this->immediateEmailLineItemsLimit = $limit;
    }

    public function placeOrder(Checkout $checkout): Order
    {
        $order = $this->createOrderByCheckout(
            $checkout,
            $checkout->getBillingAddress(),
            $this->getShippingAddress($checkout)
        );

        // set customer/customer user
        $customerUser = $checkout->getRegisteredCustomerUser();
        if ($customerUser) {
            $order->setCustomerUser($customerUser);
            $order->setCustomer($customerUser->getCustomer());
        }

        $this->flushOrder($order);

        return $order;
    }

    public function flushOrder(Order $order): void
    {
        $this->actionExecutor->executeAction('flush_entity', [$order]);
    }

    public function createOrderByCheckout(
        Checkout $checkout,
        OrderAddress $billingAddress,
        OrderAddress $shippingAddress
    ): Order {
        $orderBillingAddress = $this->addressActions->duplicateOrderAddress($billingAddress);
        $orderShippingAddress = $this->addressActions->duplicateOrderAddress($shippingAddress);

        // Get payment term
        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        // Get order line items
        $orderLineItems = $this->checkoutLineItemsManager->getData($checkout);

        $sourceEntity = $checkout->getSourceEntity()?->getSourceDocument();
        // Create order
        $additionalData = [
            'billingAddress' => $orderBillingAddress,
            'shippingAddress' => $orderShippingAddress,
            'sourceEntityClass' => is_object($sourceEntity) ? ClassUtils::getClass($sourceEntity) : null,
            'paymentTerm' => $paymentTerm,
            'lineItems' => $orderLineItems
        ];
        $order = $this->mapper->map($checkout, $additionalData);
        $this->paymentMethodsProvider->storePaymentMethodsToEntity($order, [$checkout->getPaymentMethod()]);

        // Fill totals
        $this->totalHelper->fill($order);

        return $order;
    }

    public function sendConfirmationEmail(Checkout $checkout, Order $order): void
    {
        $lineItemsCount = \count($order->getLineItems());

        if ($lineItemsCount <= $this->immediateEmailLineItemsLimit) {
            $action = 'send_order_confirmation_email';
        } else {
            $action = 'schedule_send_email_template';
        }

        $orderOwner = $order->getOwner();
        if (!$orderOwner || !$orderOwner->getEmail()) {
            return;
        }

        $this->actionExecutor->executeAction(
            $action,
            [
                'from' => ['email' => $orderOwner->getEmail(), 'name' => $orderOwner],
                'to' => [$order->getCustomerUser(), $checkout->getRegisteredCustomerUser()],
                'template' => self::ORDER_CONFIRMATION_EMAIL_TEMPLATE,
                'entity' => $order
            ]
        );
    }

    private function getShippingAddress(Checkout $checkout): OrderAddress
    {
        if ($checkout->isShipToBillingAddress()) {
            return $checkout->getBillingAddress();
        }

        return $checkout->getShippingAddress();
    }
}
