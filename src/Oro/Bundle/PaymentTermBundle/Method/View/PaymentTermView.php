<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * View class for PaymentTerm.
 */
class PaymentTermView implements PaymentMethodViewInterface
{
    /**
     * @var PaymentTermProviderInterface
     */
    protected $paymentTermProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PaymentTermConfigInterface
     */
    protected $config;

    public function __construct(
        PaymentTermProviderInterface $paymentTermProvider,
        TranslatorInterface $translator,
        PaymentTermConfigInterface $config
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->translator = $translator;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(PaymentContextInterface $context)
    {
        $paymentTerm = $this->getPaymentTerm($context);
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

    /**
     * {@inheritDoc}
     */
    public function getBlock()
    {
        return '_payment_methods_payment_term_widget';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * @param PaymentContextInterface $context
     * @return PaymentTerm|null
     */
    protected function getPaymentTerm(PaymentContextInterface $context)
    {
        $paymentTerm = null;
        $sourceEntity = $context->getSourceEntity();
        if ($sourceEntity instanceof CheckoutInterface) {
            $paymentTerm = $this->paymentTermProvider->getObjectPaymentTerm(
                $sourceEntity->getSourceEntity()
            );
        }

        if ($paymentTerm) {
            return $paymentTerm;
        }

        if ($context->getCustomer()) {
            return $this->paymentTermProvider->getPaymentTerm($context->getCustomer());
        }

        return null;
    }
}
