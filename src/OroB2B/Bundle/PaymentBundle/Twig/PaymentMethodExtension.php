<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PaymentMethodExtension extends \Twig_Extension
{
    const PAYMENT_METHOD_EXTENSION_NAME = 'orob2b_payment_method';

    /**
     * @var  PaymentTransactionProvider
     */
    protected $paymentTransactionProvider;

    /**
     * @var  PaymentMethodViewRegistry
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param PaymentMethodViewRegistry $paymentMethodViewRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(
        PaymentTransactionProvider $paymentTransactionProvider,
        PaymentMethodViewRegistry $paymentMethodViewRegistry,
        TranslatorInterface $translator
    ) {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->paymentMethodViewRegistry = $paymentMethodViewRegistry;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::PAYMENT_METHOD_EXTENSION_NAME;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_payment_methods', [$this, 'getPaymentMethods']),
            new \Twig_SimpleFunction('get_payment_method_label', [$this, 'getPaymentMethodLabel']),
            new \Twig_SimpleFunction('get_payment_method_admin_label', [$this, 'getPaymentMethodAdminLabel'])
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
            /** @var PaymentMethodViewInterface $paymentMethodView */
            $paymentMethods[] = $this->getPaymentMethodLabel($paymentTransaction->getPaymentMethod(), false);
        }

        return $paymentMethods;
    }

    /**
     * @param string $paymentMethod
     * @param bool $shortLabel
     * @return string
     */
    public function getPaymentMethodLabel($paymentMethod, $shortLabel = true)
    {
        /** @var PaymentMethodViewInterface $paymentMethodView */
        try {
            $paymentMethodView = $this->paymentMethodViewRegistry->getPaymentMethodView($paymentMethod);

            return $shortLabel? $paymentMethodView->getShortLabel() : $paymentMethodView->getLabel();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * @param string $paymentMethod
     * @return string
     */
    public function getPaymentMethodAdminLabel($paymentMethod)
    {
        $adminPaymentMethodLabel = $this->translator->trans('orob2b.payment.admin.'.$paymentMethod.'.label');
        $adminPaymentMethodShortLabel = $this->getPaymentMethodLabel($paymentMethod);

        if ($adminPaymentMethodLabel == $adminPaymentMethodShortLabel) {
            return $adminPaymentMethodLabel;
        } else {
            return $adminPaymentMethodLabel.' ('.$adminPaymentMethodShortLabel.')';
        }
    }
}
