<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm as PaymentTermMethod;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\PaymentBundle\Traits\ConfigTrait;

class PaymentTermView implements PaymentMethodViewInterface
{
    use ConfigTrait;

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /**  @var TranslatorInterface */
    protected $translator;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param TranslatorInterface $translator
     * @param ConfigManager $configManager
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        TranslatorInterface $translator,
        ConfigManager $configManager
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->translator = $translator;
        $this->configManager = $configManager;
    }

    /** {@inheritdoc} */
    public function getOptions(array $context = [])
    {
        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        if ($paymentTerm) {
            return [
                'value' => $this->translator->trans(
                    'orob2b.payment.payment_terms.label',
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
        return (int)$this->getConfigValue(Configuration::PAYMENT_TERM_SORT_ORDER_KEY);
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return $this->getConfigValue(Configuration::PAYMENT_TERM_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getShortLabel()
    {
        return $this->getConfigValue(Configuration::PAYMENT_TERM_SHORT_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PaymentTermMethod::TYPE;
    }
}
