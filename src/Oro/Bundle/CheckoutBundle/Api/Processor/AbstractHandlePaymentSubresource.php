<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\SaveParentEntity;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\SplitOrderActionsInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * The base class for processors that handle a checkout payment sub-resource.
 */
abstract class AbstractHandlePaymentSubresource implements ProcessorInterface
{
    public function __construct(
        private readonly SplitOrderActionsInterface $splitOrderActions,
        private readonly CheckoutActionsInterface $checkoutActions,
        private readonly AddressActionsInterface $addressActions,
        private readonly ActionExecutor $actionExecutor,
        private readonly PaymentStatusProviderInterface $paymentStatusProvider,
        private readonly GroupedCheckoutLineItemsProvider $groupedCheckoutLineItemsProvider,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly FlushDataHandlerInterface $flushDataHandler
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        /** @var Checkout $checkout */
        $checkout = $context->getParentEntity();
        if ($checkout->isPaymentInProgress()) {
            $this->processPaymentInProgress($checkout, $context);
        } else {
            $this->executePurchase($checkout, $context);
        }
    }

    abstract protected function getInProgressStatuses(): array;

    abstract protected function getErrorStatuses(): array;

    abstract protected function getPaymentTransactionOptions(
        Checkout $checkout,
        ChangeSubresourceContext $context
    ): array;

    abstract protected function processPaymentError(
        Checkout $checkout,
        Order $order,
        array $paymentResult,
        ChangeSubresourceContext $context
    ): void;

    protected function executePurchase(
        Checkout $checkout,
        ChangeSubresourceContext $context
    ): void {
        $checkout->setPaymentInProgress(true);

        $order = $this->splitOrderActions->placeOrder(
            $checkout,
            $this->groupedCheckoutLineItemsProvider->getGroupedLineItemsIds($checkout)
        );
        $paymentActionResult = $this->actionExecutor->executeAction(
            'payment_purchase',
            [
                'attribute' => new PropertyPath('response'),
                'object' => $order,
                'amount' => $order->getTotal(),
                'currency' => $order->getCurrency(),
                'paymentMethod' => $checkout->getPaymentMethod(),
                'transactionOptions' => $this->getPaymentTransactionOptions($checkout, $context)
            ]
        );

        $paymentResult = $paymentActionResult['response'] ?? [];
        if (!empty($paymentResult['successful'])) {
            $this->processPaymentSuccess($checkout, $order, $paymentResult, $context);
        } else {
            $this->processPaymentError($checkout, $order, $paymentResult, $context);
        }
    }

    protected function processPaymentSuccess(
        Checkout $checkout,
        Order $order,
        array $paymentResult,
        ChangeSubresourceContext $context
    ): void {
        $this->onPaymentSuccess($checkout, $order);
        $this->saveChanges($context);
        $context->setResult($order);
    }

    protected function processPaymentInProgress(Checkout $checkout, ChangeSubresourceContext $context): void
    {
        $order = $checkout->getOrder();
        if (null === $order) {
            $context->addError(Error::createValidationError(
                'payment constraint',
                'Can not process payment without order.'
            ));

            return;
        }

        $paymentStatus = $this->paymentStatusProvider->getPaymentStatus($checkout->getOrder());
        if (\in_array($paymentStatus, $this->getInProgressStatuses(), true)) {
            $context->addError(Error::createValidationError(
                'payment status constraint',
                'Payment is being processed. Please follow the payment provider\'s instructions to complete.'
            ));

            return;
        }
        if (\in_array($paymentStatus, $this->getErrorStatuses(), true)) {
            $this->onPaymentError($checkout, $context);
            $this->saveChanges($context);
            $context->addError(Error::createValidationError(
                'payment constraint',
                'Payment failed, please try again or select a different payment method.'
            ));

            return;
        }

        $this->onPaymentSuccess($checkout, $order);
        $this->saveChanges($context);
        $context->setResult($order);
    }

    protected function onPaymentSuccess(Checkout $checkout, Order $order): void
    {
        $checkout->setPaymentInProgress(false);
        $this->finishPayment($checkout, $order);
    }

    protected function onPaymentError(Checkout $checkout, ChangeSubresourceContext $context): void
    {
        $checkout->setPaymentInProgress(false);
        $order = $checkout->getOrder();
        if (null !== $order) {
            $checkout->setOrder(null);
            $context->addAdditionalEntityToRemove($order);
        }
    }

    protected function finishPayment(Checkout $checkout, Order $order): void
    {
        $this->addressActions->actualizeAddresses($checkout, $order);
        $this->checkoutActions->fillCheckoutCompletedData($checkout, $order);
    }

    protected function saveChanges(ChangeSubresourceContext $context): void
    {
        $this->flushDataHandler->flushData(
            $this->doctrineHelper->getEntityManagerForClass(Checkout::class),
            new FlushDataHandlerContext([$context], $context->getSharedData())
        );
        $context->setProcessed(SaveParentEntity::OPERATION_NAME);
    }

    protected function getPaymentAdditionalData(Checkout $checkout): array
    {
        $additionalData = $checkout->getAdditionalData();

        return $additionalData
            ? json_decode($additionalData, true, 512, JSON_THROW_ON_ERROR)
            : [];
    }
}
