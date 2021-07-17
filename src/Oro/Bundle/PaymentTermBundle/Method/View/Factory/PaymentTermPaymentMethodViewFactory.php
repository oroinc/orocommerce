<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View\Factory;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Factory for a payment term view.
 */
class PaymentTermPaymentMethodViewFactory implements PaymentTermPaymentMethodViewFactoryInterface
{
    /**
     * @var PaymentTermProviderInterface
     */
    protected $paymentTermProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(PaymentTermProviderInterface $paymentTermProvider, TranslatorInterface $translator)
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
