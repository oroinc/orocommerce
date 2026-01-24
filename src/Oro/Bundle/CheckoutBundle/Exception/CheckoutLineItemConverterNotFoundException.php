<?php

namespace Oro\Bundle\CheckoutBundle\Exception;

/**
 * Thrown when a converter for a checkout line item source cannot be found.
 *
 * Indicates that no appropriate converter exists for the given source type,
 * preventing the conversion of the source to a checkout line item.
 */
class CheckoutLineItemConverterNotFoundException extends \RuntimeException
{
    const MESSAGE_PATTERN = 'Unable to find proper converter for "%s"';

    public function __construct($source, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            self::MESSAGE_PATTERN,
            !is_object($source) ? gettype($source) : get_class($source)
        );

        parent::__construct($message, $code, $previous);
    }
}
