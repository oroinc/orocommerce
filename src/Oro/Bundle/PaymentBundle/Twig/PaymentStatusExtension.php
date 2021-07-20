<?php

namespace Oro\Bundle\PaymentBundle\Twig;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to render payment status:
 *   - get_payment_status_label
 *   - getPaymentStatus
 */
class PaymentStatusExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const PAYMENT_STATUS_EXTENSION_NAME = 'oro_payment_status';

    /** @var ContainerInterface */
    protected $container;

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
     * @return PaymentStatusProviderInterface
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
            new TwigFunction(
                'get_payment_status_label',
                [$this, 'formatPaymentStatusLabel']
            ),
            new TwigFunction(
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_payment.formatter.payment_status_label' => PaymentStatusLabelFormatter::class,
            'oro_payment.provider.payment_status' => PaymentStatusProviderInterface::class,
        ];
    }
}
