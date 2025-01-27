<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewFrontendApiOptionsInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

/**
 * Money Order payment method view.
 */
class MoneyOrderView implements PaymentMethodViewInterface, PaymentMethodViewFrontendApiOptionsInterface
{
    /**
     * @var MoneyOrderConfigInterface
     */
    protected $config;

    public function __construct(MoneyOrderConfigInterface $config)
    {
        $this->config = $config;
    }

    #[\Override]
    public function getFrontendApiOptions(PaymentContextInterface $context): array
    {
        return [
            'payTo' => $this->config->getPayTo(),
            'sendTo' => $this->config->getSendTo()
        ];
    }

    #[\Override]
    public function getOptions(PaymentContextInterface $context)
    {
        return [
            'pay_to' => $this->config->getPayTo(),
            'send_to' => $this->config->getSendTo()
        ];
    }

    #[\Override]
    public function getBlock()
    {
        return '_payment_methods_money_order_widget';
    }

    #[\Override]
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    #[\Override]
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    #[\Override]
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    #[\Override]
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }
}
