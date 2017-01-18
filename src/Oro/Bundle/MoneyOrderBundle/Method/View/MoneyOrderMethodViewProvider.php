<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

class MoneyOrderMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /** @var MoneyOrderView[] */
    private $views;

    /** @var MoneyOrderConfigProvider */
    private $configProvider;

    /**
     * @param MoneyOrderConfigProvider $configProvider
     */
    public function __construct(MoneyOrderConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param array $paymentMethods
     *
     * @return MoneyOrderView[]
     */
    public function getPaymentMethodViews(array $paymentMethods)
    {
        $views = [];
        foreach ($paymentMethods as $identifier) {
            if ($this->hasPaymentMethodView($identifier)) {
                $views[] = $this->getPaymentMethodView($identifier);
            }
        }

        return $views;
    }

    /**
     * @param string $identifier
     *
     * @return MoneyOrderView|null
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
     *
     * @return bool
     */
    public function hasPaymentMethodView($identifier)
    {
        $views = $this->getAllPaymentMethodViews();

        return array_key_exists($identifier, $views);
    }

    /**
     * @return MoneyOrderView[]
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
            $view = new MoneyOrderView($config);
            $this->views[$view->getPaymentMethodIdentifier()] = $view;
        }
    }
}
