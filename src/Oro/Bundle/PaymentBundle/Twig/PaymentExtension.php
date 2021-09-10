<?php

namespace Oro\Bundle\PaymentBundle\Twig;

use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodOptionsFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with payment method information:
 *   - get_payment_methods
 *   - get_payment_method_label
 *   - get_payment_method_admin_label
 *   - oro_payment_method_config_template
 *
 * Provides Twig functions to render payment status:
 *   - get_payment_status_label
 *   - getPaymentStatus
 */
class PaymentExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const DEFAULT_METHOD_CONFIG_TEMPLATE =
        '@OroPayment/PaymentMethodsConfigsRule/paymentMethodWithOptions.html.twig';

    private ContainerInterface $container;
    private array $configCache = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_payment_methods', [$this, 'getPaymentMethods']),
            new TwigFunction('get_payment_method_label', [$this, 'formatPaymentMethodLabel']),
            new TwigFunction('get_payment_method_admin_label', [$this, 'formatPaymentMethodAdminLabel']),
            new TwigFunction('oro_payment_method_config_template', [$this, 'getPaymentMethodConfigRenderData']),
            new TwigFunction('get_payment_status_label', [$this, 'formatPaymentStatusLabel']),
            new TwigFunction('get_payment_status', [$this, 'getPaymentStatus']),
        ];
    }

    /**
     * @param string $paymentMethod
     * @param bool   $shortLabel
     *
     * @return string
     */
    public function formatPaymentMethodLabel($paymentMethod, $shortLabel = true)
    {
        return $this->getPaymentMethodLabelFormatter()->formatPaymentMethodLabel($paymentMethod, $shortLabel);
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function formatPaymentMethodAdminLabel($paymentMethod)
    {
        return $this->getPaymentMethodLabelFormatter()->formatPaymentMethodAdminLabel($paymentMethod);
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getPaymentMethods($entity)
    {
        $paymentTransactions = $this->getPaymentTransactionProvider()->getPaymentTransactions($entity);
        $paymentMethods = [];
        $labelFormatter = $this->getPaymentMethodLabelFormatter();
        $optionsFormatter = $this->getPaymentMethodOptionsFormatter();
        foreach ($paymentTransactions as $paymentTransaction) {
            $label = $labelFormatter->formatPaymentMethodLabel($paymentTransaction->getPaymentMethod(), false);
            $options = $optionsFormatter->formatPaymentMethodOptions($paymentTransaction->getPaymentMethod());
            $paymentMethods[] = new PaymentMethodObject($label, $options);
        }

        return $paymentMethods;
    }

    /**
     * @param string $paymentMethodName
     * @return string Payment Method config template path
     */
    public function getPaymentMethodConfigRenderData($paymentMethodName)
    {
        $event = new PaymentMethodConfigDataEvent($paymentMethodName);
        if (!\array_key_exists($paymentMethodName, $this->configCache)) {
            $this->getEventDispatcher()->dispatch($event, PaymentMethodConfigDataEvent::NAME);
            $template = $event->getTemplate();
            $this->configCache[$paymentMethodName] = $template ?: self::DEFAULT_METHOD_CONFIG_TEMPLATE;
        }

        return $this->configCache[$paymentMethodName];
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
            EventDispatcherInterface::class,
            'oro_payment.provider.payment_transaction' => PaymentTransactionProvider::class,
            'oro_payment.formatter.payment_method_label' => PaymentMethodLabelFormatter::class,
            'oro_payment.formatter.payment_method_options' => PaymentMethodOptionsFormatter::class,
            'oro_payment.formatter.payment_status_label' => PaymentStatusLabelFormatter::class,
            'oro_payment.provider.payment_status' => PaymentStatusProviderInterface::class,
        ];
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get(EventDispatcherInterface::class);
    }

    private function getPaymentTransactionProvider(): PaymentTransactionProvider
    {
        return $this->container->get('oro_payment.provider.payment_transaction');
    }

    private function getPaymentMethodLabelFormatter(): PaymentMethodLabelFormatter
    {
        return $this->container->get('oro_payment.formatter.payment_method_label');
    }

    private function getPaymentMethodOptionsFormatter(): PaymentMethodOptionsFormatter
    {
        return $this->container->get('oro_payment.formatter.payment_method_options');
    }

    private function getPaymentStatusLabelFormatter(): PaymentStatusLabelFormatter
    {
        return $this->container->get('oro_payment.formatter.payment_status_label');
    }

    private function getPaymentStatusProvider(): PaymentStatusProviderInterface
    {
        return $this->container->get('oro_payment.provider.payment_status');
    }
}
