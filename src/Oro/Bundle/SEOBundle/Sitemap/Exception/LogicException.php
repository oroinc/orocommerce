<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Exception;

/**
 * Thrown when a logic error occurs during sitemap processing.
 *
 * This exception is raised when an unexpected or invalid state is encountered during sitemap generation,
 * such as missing required configuration, invalid provider setup, or other logic-related issues.
 */
class LogicException extends \LogicException
{
}
