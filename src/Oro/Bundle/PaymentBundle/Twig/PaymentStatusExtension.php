<?php

namespace Oro\Bundle\PaymentBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;

class PaymentStatusExtension extends \Twig_Extension
{
    const PAYMENT_STATUS_EXTENSION_NAME = 'oro_payment_status';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return PaymentStatusLabelFormatter
     */
    protected function getPaymentStatusLabelFormatter()
    {
        return $this->container->get('oro_payment.formatter.payment_status_label');
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
                [$this, 'formatPaymentStatusLabel'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * @param string $paymentStatus
     *
     * @return string
     */
    public function formatPaymentStatusLabel($paymentStatus)
    {
        return $this->getPaymentStatusLabelFormatter()->formatPaymentStatusLabel($paymentStatus);
    }
}
