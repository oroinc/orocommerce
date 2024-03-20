<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;

class OrderActions
{
    private const ORDER_CONFIRMATION_EMAIL_TEMPLATE = 'order_confirmation_email';

    private int $immediateEmailLineItemsLimit = 10;

    public function __construct(
        private ManagerRegistry $registry,
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

    public function placeOrder(Checkout $checkout): array
    {
        $order = $this->createOrderByCheckout(
            $checkout,
            $checkout->getBillingAddress(),
            $this->getShippingAddress($checkout)
        )['order'];

        // set customer/customer user
        if ($checkout->getRegisteredCustomerUser()) {
            $customerUser = $checkout->getRegisteredCustomerUser();
            $order->setCustomerUser($customerUser);
            $order->setCustomer($customerUser->getCustomer());
        }

        $this->flushOrder($order);

        return ['order' => $order];
    }

    public function flushOrder(Order $order): void
    {
        $em = $this->registry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush($order);
    }

    public function createOrderByCheckout(
        Checkout $checkout,
        OrderAddress $billingAddress,
        OrderAddress $shippingAddress
    ): array {
        $orderBillingAddress = $this->addressActions->duplicateOrderAddress($billingAddress)['newAddress'];
        $orderShippingAddress = $this->addressActions->duplicateOrderAddress($shippingAddress)['newAddress'];

        // Get payment term
        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        // Get order line items
        $orderLineItems = $this->checkoutLineItemsManager->getData($checkout);

        $sourceEntity = $checkout->getSourceEntity();
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

        return ['order' => $order];
    }

    public function sendConfirmationEmail(Checkout $checkout, Order $order): void
    {
        $lineItemsCount = count($order->getLineItems());

        if ($lineItemsCount <= $this->immediateEmailLineItemsLimit) {
            $action = 'send_order_confirmation_email';
        } else {
            $action = 'schedule_send_email_template';
        }

        $this->actionExecutor->executeAction(
            $action,
            [
                'from' => $order->getOwner()->getEmail(),
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
