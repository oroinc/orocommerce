<?php

namespace Oro\Bundle\CheckoutBundle\Exception;

class CheckoutLineItemConverterNotFoundException extends \RuntimeException
{
    const MESSAGE_PATTERN = 'Unable to find proper converter for "%s"';

    /**
     * {@inheritDoc}
     */
    public function __construct($source, $code = 0, \Throwable $previous = null)
    {
        $message = sprintf(
            self::MESSAGE_PATTERN,
            !is_object($source) ? gettype($source) : get_class($source)
        );

        parent::__construct($message, $code, $previous);
    }
}
