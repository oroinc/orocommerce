<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Formatter;

use Oro\Bundle\PaymentBundle\Provider\AvailablePaymentStatusesProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Format labels for payment statuses.
 */
class PaymentStatusLabelFormatter
{
    public function __construct(
        private readonly AvailablePaymentStatusesProvider $availablePaymentStatusesProvider,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function formatPaymentStatusLabel(string $paymentStatus): string
    {
        $translationKey = $this->getTranslationKey($paymentStatus);
        $label = $this->translator->trans($translationKey);
        if ($label === $translationKey) {
            // If the label is not translated, use the payment status as the label - transformed
            // to human-readable format.
            $label = ucfirst(str_replace(['_', '-'], ' ', strtolower($paymentStatus)));
        }

        return $label;
    }

    /**
     * @return array<string,string> Associative array of payment statuses keyed by their labels
     *  [
     *      'Paid in Full' => 'paid_in_full',
     *      // ...
     *  ]
     */
    public function getAvailableStatuses(?string $entityClass = null): array
    {
        $availablePaymentStatuses = $this->availablePaymentStatusesProvider->getAvailablePaymentStatuses($entityClass);
        $result = [];

        foreach ($availablePaymentStatuses as $paymentStatus) {
            $result[$this->formatPaymentStatusLabel($paymentStatus)] = $paymentStatus;
        }

        return $result;
    }

    private function getTranslationKey(string $paymentStatus): string
    {
        return sprintf('oro.payment.status.%s', $paymentStatus);
    }
}
