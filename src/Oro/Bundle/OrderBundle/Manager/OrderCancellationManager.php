<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderPaymentStatusProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class provides methods for order cancellation.
 */
class OrderCancellationManager
{
    public function __construct(
        protected readonly ManagerRegistry $doctrine,
        protected readonly ConfigManager $configManager,
        protected readonly OrderPaymentStatusProvider $orderPaymentStatusProvider,
        protected readonly TranslatorInterface $translator,
        protected array $internalStatusesAllowingCancellation,
        protected array $shippingStatusesAllowingCancellation,
        protected array $paymentStatusesAllowingCancellation,
    ) {
    }

    /**
     * @param array $internalStatusesAllowingCancellation internal status IDs, e.g.:
     *
     *      [
     *          \Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
     *          \Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface::INTERNAL_STATUS_PENDING
     *      ]
     */
    public function setInternalStatusesAllowingCancellation(array $internalStatusesAllowingCancellation): void
    {
        $this->internalStatusesAllowingCancellation = $internalStatusesAllowingCancellation;
    }

    /**
     * @param array $shippingStatusesAllowingCancellation shipping status IDs, e.g.:
     *
     *      ['not_shipped']
     */
    public function setShippingStatusesAllowingCancellation(array $shippingStatusesAllowingCancellation): void
    {
        $this->shippingStatusesAllowingCancellation = $shippingStatusesAllowingCancellation;
    }

    /**
     * @param array $paymentStatusesAllowingCancellation payment status IDs, e.g.:
     *
     *      [
     *          \Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses::PENDING,
     *          \Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses::DECLINED,
     *          \Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses::CANCELED,
     *      ]
     */
    public function setPaymentStatusesAllowingCancellation(array $paymentStatusesAllowingCancellation): void
    {
        $this->paymentStatusesAllowingCancellation = $paymentStatusesAllowingCancellation;
    }

    /**
     * Returns true if the order can be canceled.
     */
    public function canBeCanceled(Order $order): bool
    {
        $internalStatusId = $order->getInternalStatus()->getInternalId();
        $shippingStatusId = $order->getShippingStatus()->getInternalId();
        $paymentStatus = $this->orderPaymentStatusProvider->getPaymentStatus($order);
        return \in_array($internalStatusId, $this->internalStatusesAllowingCancellation, true)
            && \in_array($shippingStatusId, $this->shippingStatusesAllowingCancellation, true)
            && \in_array($paymentStatus, $this->paymentStatusesAllowingCancellation, true);
    }

    /**
     * Returns reasons why the order cannot be canceled.
     *
     * @return string[]
     */
    public function getCannotBeCanceledReasons(Order $order): array
    {
        $reasons = [];
        if (!\in_array(
            $order->getInternalStatus()->getInternalId(),
            $this->internalStatusesAllowingCancellation,
            true
        )) {
            $message = $this->translator->trans(
                'oro.order.cannot_be_canceled_reason.not_allowed_internal_status',
                [
                    '%status%' => $order->getInternalStatus()->getName(),
                    '%allowedStatuses%' => implode(', ', $this->internalStatusesAllowingCancellation),
                ]
            );
            $reasons[] = $message;
        }
        if (!\in_array(
            $order->getShippingStatus()->getInternalId(),
            $this->shippingStatusesAllowingCancellation,
            true
        )) {
            $message = $this->translator->trans(
                'oro.order.cannot_be_canceled_reason.not_allowed_shipping_status',
                [
                    '%status%' => $order->getShippingStatus()->getName(),
                    '%allowedStatuses%' => implode(', ', $this->shippingStatusesAllowingCancellation),
                ]
            );
            $reasons[] = $message;
        }
        $paymentStatus = $this->orderPaymentStatusProvider->getPaymentStatus($order);
        if (!\in_array(
            $paymentStatus,
            $this->paymentStatusesAllowingCancellation,
            true
        )) {
            $message = $this->translator->trans(
                'oro.order.cannot_be_canceled_reason.not_allowed_payment_status',
                [
                    '%status%' => $paymentStatus,
                    '%allowedStatuses%' => \implode(', ', $this->paymentStatusesAllowingCancellation),
                ]
            );
            $reasons[] = $message;
        }
        return $reasons;
    }

    /**
     * Sets order's internal status to "cancelled".
     *
     * @throws \RuntimeException if the order cannot be canceled
     */
    public function cancel(Order $order): void
    {
        if (!$this->canBeCanceled($order)) {
            throw new \RuntimeException(
                \implode(', ', $this->getCannotBeCanceledReasons($order))
            );
        }

        $order->setInternalStatus($this->getInternalOrderStatusCanceled());
    }

    /**
     * Returns EnumOption entity for "cancelled" internal order status.
     */
    public function getInternalOrderStatusCanceled(): EnumOption
    {
        return $this->doctrine->getRepository(EnumOption::class)
            ->find(ExtendHelper::buildEnumOptionId(
                Order::INTERNAL_STATUS_CODE,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
            ))
        ;
    }

    public function isCanceled(Order $order): bool
    {
        return $order->getInternalStatus()->getInternalId()
            === OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED;
    }
}
