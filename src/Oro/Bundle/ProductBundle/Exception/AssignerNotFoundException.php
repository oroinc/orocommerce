<?php

namespace Oro\Bundle\ProductBundle\Exception;

/**
 * Thrown when a related item assigner cannot be found.
 *
 * This exception is raised when attempting to retrieve an assigner for related items
 * (such as related products or upsell products) but the requested assigner is not registered.
 */
class AssignerNotFoundException extends \LogicException
{
}
