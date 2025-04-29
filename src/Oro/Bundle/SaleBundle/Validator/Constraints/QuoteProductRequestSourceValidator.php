<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a quote product request is created from the same RFQ as the quote.
 */
class QuoteProductRequestSourceValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof QuoteProductRequestSource) {
            throw new UnexpectedTypeException($constraint, QuoteProductRequestSource::class);
        }

        if (!$value instanceof QuoteProductRequest) {
            throw new UnexpectedTypeException($value, QuoteProductRequest::class);
        }

        $requestSource = $value->getRequestProductItem()?->getRequestProduct()?->getRequest();
        if (null === $requestSource) {
            return;
        }

        if ($value->getQuoteProduct()?->getQuote()?->getRequest() !== $requestSource) {
            $this->context->buildViolation($constraint->message)
                ->atPath('requestProductItem')
                ->addViolation();
        }
    }
}
