<?php

namespace Oro\Bundle\PaymentBundle\Formatter;

use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Provider\AvailablePaymentStatusesProvider;
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

    private ?AvailablePaymentStatusesProvider $availablePaymentStatusesProvider = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setAvailablePaymentStatusesProvider(
        ?AvailablePaymentStatusesProvider $availablePaymentStatusesProvider
    ): void {
        $this->availablePaymentStatusesProvider = $availablePaymentStatusesProvider;
    }

    /**
     * @param string $paymentStatus
     * @return string
     */
    public function formatPaymentStatusLabel($paymentStatus)
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
     * @param string|null $entityClass The class name of the entity to get payment statuses for. The argument will be
     *  added in v7.0.
     *
     * @return array<string,string> Associative array of payment statuses keyed by their labels
     *  [
     *      'Paid in Full' => 'full',
     *      // ...
     *  ]
     */
    public function getAvailableStatuses(/*?string $entityClass = null*/)
    {
        // BC layer.
        if (!$this->availablePaymentStatusesProvider) {
            $availablePaymentStatuses = PaymentStatuses::getAllPaymentStatuses();
        } else {
            $entityClass = func_num_args() > 0 ? func_get_arg(0) : null;
            $availablePaymentStatuses = $this->availablePaymentStatusesProvider
                ->getAvailablePaymentStatuses($entityClass);
        }

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
