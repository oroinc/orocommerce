<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Label\Type;

use Oro\Bundle\ShippingBundle\Method\Exception\InvalidArgumentException;

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
