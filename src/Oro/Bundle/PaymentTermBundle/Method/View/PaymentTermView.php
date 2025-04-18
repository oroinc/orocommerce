<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewFrontendApiOptionsInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PaymentTerm payment method view.
 */
class PaymentTermView implements PaymentMethodViewInterface, PaymentMethodViewFrontendApiOptionsInterface
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

    #[\Override]
    public function getFrontendApiOptions(PaymentContextInterface $context): array
    {
        return [
            'paymentTerm' => $this->getPaymentTerm($context)?->getLabel()
        ];
    }

    #[\Override]
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

    #[\Override]
    public function getBlock()
    {
        return '_payment_methods_payment_term_widget';
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

    /**
     * @param PaymentContextInterface $context
     * @return PaymentTerm|null
     */
    protected function getPaymentTerm(PaymentContextInterface $context)
    {
        $paymentTerm = null;
        $sourceEntity = $context->getSourceEntity();
        if ($sourceEntity instanceof CheckoutInterface) {
            $source = $sourceEntity->getSourceEntity();
            if (null !== $source) {
                $paymentTerm = $this->paymentTermProvider->getObjectPaymentTerm($source);
            }
        }
        if (!$paymentTerm) {
            $customer = $context->getCustomer();
            if (null !== $customer) {
                $paymentTerm = $this->paymentTermProvider->getPaymentTerm($customer);
            }
        }

        return $paymentTerm;
    }
}
