<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm as PaymentTermMethod;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermView implements PaymentMethodViewInterface
{
    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /**  @var TranslatorInterface */
    protected $translator;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        TranslatorInterface $translator
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->translator = $translator;
    }

    /** {@inheritdoc} */
    public function getOptions()
    {
        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        if ($paymentTerm) {
            return ['value' => $paymentTerm->getLabel()];
        }

        return [];
    }

    /** {@inheritdoc} */
    public function getTemplate()
    {
        return 'OroB2BPaymentBundle:PaymentMethod:plain.html.twig';
    }

    /** {@inheritdoc} */
    public function getOrder()
    {
        /* @todo: config */
        return 0;
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return $this->translator->trans('orob2b.payment.methods.term_method.label');
    }

    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PaymentTermMethod::TYPE;
    }
}
