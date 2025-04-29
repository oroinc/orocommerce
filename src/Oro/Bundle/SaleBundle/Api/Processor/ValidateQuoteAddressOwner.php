<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Validates the quote association for the new quote shipping address.
 */
class ValidateQuoteAddressOwner implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $quoteForm = $context->findFormField('quote');
        if (null === $quoteForm) {
            return;
        }

        if ($quoteForm->isSubmitted() && null !== $quoteForm->getData()) {
            /** @var QuoteAddress|null $quote */
            $quote = $quoteForm->getData();
            if (null !== $quote && null !== $quote->getShippingAddress()) {
                FormUtil::addNamedFormError(
                    $quoteForm,
                    'quote shipping address constraint',
                    'This quote already has a shipping address.'
                );
            }
        } elseif ($context->isPrimaryEntityRequest()) {
            FormUtil::addFormConstraintViolation($quoteForm, new NotBlank());
        }
    }
}
