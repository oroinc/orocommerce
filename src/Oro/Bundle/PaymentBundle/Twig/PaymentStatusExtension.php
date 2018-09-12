<?php

namespace Oro\Bundle\PaymentBundle\Twig;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twig extension that provides payment status
 */
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
     * @return PaymentStatusProvider
     */
    protected function getPaymentStatusProvider()
    {
        return $this->container->get('oro_payment.provider.payment_status');
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
            ),
            new \Twig_SimpleFunction(
                'get_payment_status',
                [$this, 'getPaymentStatus']
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

    /**
     * @param object $entity
     *
     * @return string
     */
    public function getPaymentStatus($entity)
    {
        return $this->getPaymentStatusProvider()->getPaymentStatus($entity);
    }
}
