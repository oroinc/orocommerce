<?php

namespace OroB2B\Bundle\PaymentBundle\Formatter;

use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

use Symfony\Component\Translation\TranslatorInterface;

class PaymentStatusLabelFormatter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
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
        return $this->translator->trans(sprintf('orob2b.payment.status.%s', $paymentStatus));
    }
    
    /**
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [
            PaymentStatusProvider::FULL => $this->formatPaymentStatusLabel(PaymentStatusProvider::FULL),
            PaymentStatusProvider::AUTHORIZED => $this->formatPaymentStatusLabel(PaymentStatusProvider::AUTHORIZED),
            PaymentStatusProvider::PENDING => $this->formatPaymentStatusLabel(PaymentStatusProvider::PENDING),
            PaymentStatusProvider::DECLINED => $this->formatPaymentStatusLabel(PaymentStatusProvider::DECLINED),
            PaymentStatusProvider::PARTIALLY => $this->formatPaymentStatusLabel(PaymentStatusProvider::PARTIALLY),
        ];
    }
}
