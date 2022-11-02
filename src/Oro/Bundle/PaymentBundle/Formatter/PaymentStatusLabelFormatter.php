<?php

namespace Oro\Bundle\PaymentBundle\Formatter;

use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Format labels for payment statuses.
 */
class PaymentStatusLabelFormatter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $paymentStatus
     * @return string
     */
    public function formatPaymentStatusLabel($paymentStatus)
    {
        return $this->translator->trans(sprintf('oro.payment.status.%s', $paymentStatus));
    }

    /**
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            $this->formatPaymentStatusLabel(PaymentStatusProvider::FULL) => PaymentStatusProvider::FULL,
            $this->formatPaymentStatusLabel(PaymentStatusProvider::AUTHORIZED) => PaymentStatusProvider::AUTHORIZED,
            $this->formatPaymentStatusLabel(PaymentStatusProvider::PENDING) => PaymentStatusProvider::PENDING,
            $this->formatPaymentStatusLabel(PaymentStatusProvider::DECLINED) => PaymentStatusProvider::DECLINED,
            $this->formatPaymentStatusLabel(PaymentStatusProvider::PARTIALLY) => PaymentStatusProvider::PARTIALLY,
            $this->formatPaymentStatusLabel(PaymentStatusProvider::CANCELED) => PaymentStatusProvider::CANCELED,
            $this->formatPaymentStatusLabel(PaymentStatusProvider::CANCELED_PARTIALLY) =>
                PaymentStatusProvider::CANCELED_PARTIALLY
        ];
    }
}
