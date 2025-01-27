<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a quote is acceptable.
 */
class QuoteAcceptable extends Constraint
{
    public const string CODE = 'quote_acceptable';

    public bool $default = true;
    public string $message = 'oro.frontend.sale.message.quote.not_available';

    #[\Override]
    public function getTargets(): string|array
    {
        return [
            self::CLASS_CONSTRAINT,
            self::PROPERTY_CONSTRAINT
        ];
    }
}
