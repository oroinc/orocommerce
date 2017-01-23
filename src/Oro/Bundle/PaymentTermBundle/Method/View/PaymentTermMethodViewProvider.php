<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentTermMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /**
     * @var  PaymentMethodViewInterface[]
     */
    protected $methodViews;
    
    /**
     * @var PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     *  @var TranslatorInterface
     */
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

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodViews(array $identifiers)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        $matchedViews = [];
        foreach ($identifiers as $paymentMethod) {
            if ($this->hasPaymentMethodView($paymentMethod)) {
                $matchedViews[$paymentMethod] = $this->getPaymentMethodView($paymentMethod);
            }
        }
        return $matchedViews;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        if ($this->hasPaymentMethodView($identifier)) {
            return $this->methodViews[$identifier];
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        return array_key_exists($identifier, $this->methodViews);
    }

    protected function collectPaymentMethodViews()
    {
        /**
         * @TODO: fix in BB-7058
         */
        $this->methodViews = [];

        return;

        $methodView = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->config);
        $this->methodViews = [PaymentTerm::TYPE => $methodView];
    }
}
