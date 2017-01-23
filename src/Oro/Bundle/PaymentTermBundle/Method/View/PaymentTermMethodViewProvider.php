<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\Provider\PaymentTermConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentTermMethodViewProvider implements PaymentMethodViewProviderInterface
{
   /** @var PaymentTermView[] */
    private $views;

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PaymentTermConfigProviderInterface */
    private $configProvider;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param TranslatorInterface $translator
     * @param PaymentTermConfigProviderInterface $configProvider
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        TranslatorInterface $translator,
        PaymentTermConfigProviderInterface $configProvider
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->translator = $translator;
        $this->configProvider = $configProvider;
    }

    /**
     * @param array $identifiers
     * @return PaymentTermView[]
     */
    public function getPaymentMethodViews(array $identifiers)
    {
        $views = [];
        foreach ($identifiers as $paymentMethod) {
            if ($this->hasPaymentMethodView($paymentMethod)) {
                $views[] = $this->getPaymentMethodView($paymentMethod);
            }
        }
        return $views;
    }

    /**
     * @param string $identifier
     * @return PaymentTermView|null
     */
    public function getPaymentMethodView($identifier)
    {
        if (!$this->hasPaymentMethodView($identifier)) {
            return null;
        }
        $views = $this->getAllPaymentMethodViews();
        return $views[$identifier];
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethodView($identifier)
    {
        $views = $this->getAllPaymentMethodViews();
        return array_key_exists($identifier, $views);
    }

    /**
     * @return PaymentTermView[]
     */
    private function getAllPaymentMethodViews()
    {
        if (empty($this->views)) {
            $this->collectPaymentMethodViews();
        }
        return $this->views;
    }

    private function collectPaymentMethodViews()
    {
        foreach ($this->configProvider->getPaymentConfigs() as $config) {
            $view = new PaymentTermView($this->paymentTermProvider, $this->translator, $config);
            $this->views[$view->getPaymentMethodIdentifier()] = $view;
        }
    }
}
