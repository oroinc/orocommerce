<?php

namespace Oro\Bundle\PaymentBundle\Formatter;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;

use Symfony\Component\Translation\TranslatorInterface;

class PaymentMethodLabelFormatter
{
    /**
     * @var PaymentMethodViewRegistry
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param PaymentMethodViewRegistry $paymentMethodViewRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(
        PaymentMethodViewRegistry $paymentMethodViewRegistry,
        TranslatorInterface $translator
    ) {
        $this->paymentMethodViewRegistry = $paymentMethodViewRegistry;
        $this->translator = $translator;
    }


    /**
     * @param string $paymentMethod
     * @param bool $shortLabel
     * @return string
     */
    public function formatPaymentMethodLabel($paymentMethod, $shortLabel = true)
    {
        try {
            $paymentMethodView = $this->paymentMethodViewRegistry->getPaymentMethodView($paymentMethod);

            return $shortLabel ? $paymentMethodView->getShortLabel() : $paymentMethodView->getLabel();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * @param string $paymentMethod
     * @return string
     */
    public function formatPaymentMethodAdminLabel($paymentMethod)
    {
        $adminPaymentMethodLabel = $this->translator->trans(
            sprintf(
                'oro.payment.admin.%s.label',
                $paymentMethod
            )
        );
        $adminPaymentMethodShortLabel = $this->formatPaymentMethodLabel($paymentMethod);

        if ($adminPaymentMethodLabel == $adminPaymentMethodShortLabel) {
            return $adminPaymentMethodLabel;
        } else {
            return sprintf(
                '%s (%s)',
                $adminPaymentMethodShortLabel,
                $adminPaymentMethodLabel
            );
        }
    }
}
