<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that a payment method is set to an order.
 */
class ValidatePaymentMethodExists implements ProcessorInterface
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        if (PaymentOptionsContextUtil::has(
            $context->getSharedData(),
            $context->getData(),
            PaymentOptionsContextUtil::PAYMENT_METHOD
        )) {
            return;
        }

        FormUtil::addNamedFormError(
            $context->getForm(),
            'payment method constraint',
            $this->translator->trans('oro.payment.methods.no_method')
        );
    }
}
