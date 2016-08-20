<?php

namespace Oro\Bundle\PaymentBundle\Twig;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;

class PaymentStatusExtension extends \Twig_Extension
{
    const PAYMENT_STATUS_EXTENSION_NAME = 'orob2b_payment_status';

    /** @var PaymentStatusLabelFormatter */
    protected $paymentStatusLabelFormatter;

    /**
     * @param PaymentStatusLabelFormatter $paymentStatusLabelFormatter
     */
    public function __construct(PaymentStatusLabelFormatter $paymentStatusLabelFormatter)
    {
        $this->paymentStatusLabelFormatter = $paymentStatusLabelFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::PAYMENT_STATUS_EXTENSION_NAME;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'get_payment_status_label',
                [$this->paymentStatusLabelFormatter, 'formatPaymentStatusLabel'],
                ['is_safe' => ['html']]
            )
        ];
    }
}
