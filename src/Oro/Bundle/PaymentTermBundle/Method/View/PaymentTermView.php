<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm as PaymentTermMethod;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

class PaymentTermView implements PaymentMethodViewInterface
{
    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /**  @var TranslatorInterface */
    protected $translator;

    /** @var PaymentTermConfigInterface */
    protected $config;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param TranslatorInterface $translator
     * @param PaymentTermConfigInterface $config
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        TranslatorInterface $translator,
        PaymentTermConfigInterface $config
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->translator = $translator;
        $this->config = $config;
    }

    /** {@inheritdoc} */
    public function getOptions(array $context = [])
    {
        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        if ($paymentTerm) {
            return [
                'value' => $this->translator->trans(
                    'oro.paymentterm.payment_terms.label',
                    ['%paymentTerm%' => $paymentTerm->getLabel()]
                ),
            ];
        }

        return [];
    }

    /** {@inheritdoc} */
    public function getBlock()
    {
        return '_payment_methods_payment_term_widget';
    }

    /** {@inheritdoc} */
    public function getOrder()
    {
        return $this->config->getOrder();
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /** {@inheritdoc} */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PaymentTermMethod::TYPE;
    }
}
