<?php

namespace Oro\Bundle\PaymentBundle\Twig;

use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentMethodExtension extends \Twig_Extension
{
    const DEFAULT_METHOD_CONFIG_TEMPLATE =
        'OroPaymentBundle:PaymentMethodsConfigsRule:paymentMethodWithOptions.html.twig';

    /**
     * @var  PaymentTransactionProvider
     */
    protected $paymentTransactionProvider;

    /**
     * @var PaymentMethodLabelFormatter
     */
    protected $paymentMethodLabelFormatter;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $configCache = [];

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param PaymentMethodLabelFormatter $paymentMethodLabelFormatter
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        PaymentTransactionProvider $paymentTransactionProvider,
        PaymentMethodLabelFormatter $paymentMethodLabelFormatter,
        EventDispatcherInterface $dispatcher
    ) {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->paymentMethodLabelFormatter = $paymentMethodLabelFormatter;
        $this->dispatcher = $dispatcher;
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
                [$this->paymentMethodLabelFormatter, 'formatPaymentMethodLabel']
            ),
            new \Twig_SimpleFunction(
                'get_payment_method_admin_label',
                [$this->paymentMethodLabelFormatter, 'formatPaymentMethodAdminLabel'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_payment_method_config_template',
                [$this, 'getPaymentMethodConfigRenderData']
            )
        ];
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getPaymentMethods($entity)
    {
        $paymentTransactions = $this->paymentTransactionProvider->getPaymentTransactions($entity);
        $paymentMethods = [];
        foreach ($paymentTransactions as $paymentTransaction) {
            $paymentMethods[] = $this->paymentMethodLabelFormatter
                ->formatPaymentMethodLabel(
                    $paymentTransaction->getPaymentMethod(),
                    false
                );
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
            $this->dispatcher->dispatch(PaymentMethodConfigDataEvent::NAME, $event);
            $template = $event->getTemplate();
            $this->configCache[$paymentMethodName] = $template ?: static::DEFAULT_METHOD_CONFIG_TEMPLATE;
        }

        return $this->configCache[$paymentMethodName];
    }
}
