<?php

namespace Oro\Bundle\PaymentBundle\Context\Factory\Exception;

/**
 * Thrown when an entity type is not supported by the payment context factory.
 *
 * This exception is raised when attempting to create a payment context for an entity class
 * that is not registered with any of the available payment context factories.
 */
class UnsupportedEntityException extends \InvalidArgumentException
{
}
