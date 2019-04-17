<?php

namespace Oro\Bundle\PaymentBundle\Twig;

use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodOptionsFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Twig extension that provides data based on payment method or entity that was participated in payment transactions
 */
class PaymentMethodExtension extends \Twig_Extension
{
    const DEFAULT_METHOD_CONFIG_TEMPLATE =
        'OroPaymentBundle:PaymentMethodsConfigsRule:paymentMethodWithOptions.html.twig';

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $configCache = [];

    /**
     * @param ContainerInterface $container
     */
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
            new \Twig_SimpleFunction('get_payment_methods', [$this, 'getPaymentMethods']),
            new \Twig_SimpleFunction(
                'get_payment_method_label',
                [$this, 'formatPaymentMethodLabel']
            ),
            new \Twig_SimpleFunction(
                'get_payment_method_admin_label',
                [$this, 'formatPaymentMethodAdminLabel']
            ),
            new \Twig_SimpleFunction(
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
            $this->getDispatcher()->dispatch(PaymentMethodConfigDataEvent::NAME, $event);
            $template = $event->getTemplate();
            $this->configCache[$paymentMethodName] = $template ?: static::DEFAULT_METHOD_CONFIG_TEMPLATE;
        }

        return $this->configCache[$paymentMethodName];
    }
}
