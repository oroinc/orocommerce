<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a quote is acceptable.
 */
class QuoteAcceptable extends Constraint
{
    public const string CODE = 'quote_acceptable';

    public string $message = 'oro.frontend.sale.message.quote.not_available';
    public bool $default = true;

    #[\Override]
    public function getTargets(): string|array
    {
        return [
            self::CLASS_CONSTRAINT,
            self::PROPERTY_CONSTRAINT
        ];
    }
}
