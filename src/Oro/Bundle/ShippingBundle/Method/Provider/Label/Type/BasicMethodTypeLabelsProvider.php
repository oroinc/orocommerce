<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Label\Type;

use Oro\Bundle\ShippingBundle\Method\Exception\InvalidArgumentException;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Provides shipping method type labels from the shipping method provider.
 *
 * This provider retrieves localized labels for shipping method types by querying the shipping method provider,
 * validating that both the method and types exist before returning their labels.
 */
class BasicMethodTypeLabelsProvider implements MethodTypeLabelsProviderInterface
{
    /**
     * @var ShippingMethodProviderInterface
     */
    private $methodProvider;

    public function __construct(ShippingMethodProviderInterface $methodProvider)
    {
        $this->methodProvider = $methodProvider;
    }

    #[\Override]
    public function getLabels($methodIdentifier, array $typeIdentifiers)
    {
        $method = $this->methodProvider->getShippingMethod($methodIdentifier);
        if (!$method) {
            throw new InvalidArgumentException(
                sprintf('Shipping method with identifier: %s, does not exist.', $methodIdentifier)
            );
        }

        $labels = [];
        foreach ($typeIdentifiers as $typeIdentifier) {
            $type = $method->getType($typeIdentifier);
            if (!$type) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Shipping method with identifier: %s does not contain type with identifier: %s.',
                        $methodIdentifier,
                        $typeIdentifier
                    )
                );
            }
            $labels[] = $type->getLabel();
        }

        return $labels;
    }
}
