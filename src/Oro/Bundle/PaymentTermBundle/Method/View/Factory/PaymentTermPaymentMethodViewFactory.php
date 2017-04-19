<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View\Factory;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentTermPaymentMethodViewFactory implements PaymentTermPaymentMethodViewFactoryInterface
{
    /**
     * @var PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(PaymentTermProvider $paymentTermProvider, TranslatorInterface $translator)
    {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PaymentTermConfigInterface $config)
    {
        return new PaymentTermView($this->paymentTermProvider, $this->translator, $config);
    }
}
