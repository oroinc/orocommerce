<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Label\Type;

use Oro\Bundle\ShippingBundle\Method\Exception\InvalidArgumentException;

/**
 * Defines the contract for providers that retrieve shipping method type labels.
 *
 * Implementations of this interface provide localized labels for shipping method types
 * (e.g., "Ground", "Express", "Overnight") based on method and type identifiers,
 * used for displaying shipping options in the user interface.
 */
interface MethodTypeLabelsProviderInterface
{
    /**
     * @param string   $methodIdentifier
     * @param string[] $typeIdentifiers
     *
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public function getLabels($methodIdentifier, array $typeIdentifiers);
}
