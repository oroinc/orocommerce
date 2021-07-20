<?php

namespace Oro\Bundle\PaymentBundle\Twig;

use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodOptionsFormatter;
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
 */
class PaymentMethodExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const DEFAULT_METHOD_CONFIG_TEMPLATE =
        'OroPaymentBundle:PaymentMethodsConfigsRule:paymentMethodWithOptions.html.twig';

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $configCache = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return PaymentTransactionProvider
     */
    protected function getPaymentTransactionProvider()
    {
        return $this->container->get('oro_payment.provider.payment_transaction');
    }

    /**
     * @return PaymentMethodLabelFormatter
     */
    protected function getPaymentMethodLabelFormatter()
    {
        return $this->container->get('oro_payment.formatter.payment_method_label');
    }

    /**
     * @return PaymentMethodOptionsFormatter
     */
    protected function getPaymentMethodOptionsFormatter()
    {
        return $this->container->get('oro_payment.formatter.payment_method_options');
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_payment_methods', [$this, 'getPaymentMethods']),
            new TwigFunction(
                'get_payment_method_label',
                [$this, 'formatPaymentMethodLabel']
            ),
            new TwigFunction(
                'get_payment_method_admin_label',
                [$this, 'formatPaymentMethodAdminLabel']
            ),
            new TwigFunction(
                'oro_payment_method_config_template',
                [$this, 'getPaymentMethodConfigRenderData']
            )
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
        if (!array_key_exists($paymentMethodName, $this->configCache)) {
            $this->getDispatcher()->dispatch($event, PaymentMethodConfigDataEvent::NAME);
            $template = $event->getTemplate();
            $this->configCache[$paymentMethodName] = $template ?: static::DEFAULT_METHOD_CONFIG_TEMPLATE;
        }

        return $this->configCache[$paymentMethodName];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_payment.provider.payment_transaction' => PaymentTransactionProvider::class,
            'oro_payment.formatter.payment_method_label' => PaymentMethodLabelFormatter::class,
            'oro_payment.formatter.payment_method_options' => PaymentMethodOptionsFormatter::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ];
    }
}
