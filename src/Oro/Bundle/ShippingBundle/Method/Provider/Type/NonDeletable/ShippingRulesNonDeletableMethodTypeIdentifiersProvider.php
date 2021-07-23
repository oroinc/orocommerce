<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Type\NonDeletable;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class ShippingRulesNonDeletableMethodTypeIdentifiersProvider implements
    NonDeletableMethodTypeIdentifiersProviderInterface
{
    /**
     * @var ShippingMethodTypeConfigRepository
     */
    private $methodTypeConfigRepository;

    public function __construct(ShippingMethodTypeConfigRepository $methodTypeConfigRepository)
    {
        $this->methodTypeConfigRepository = $methodTypeConfigRepository;
    }

    /**
     * @param ShippingMethodInterface $shippingMethod
     *
     * @return string[]
     */
    public function getMethodTypeIdentifiers(ShippingMethodInterface $shippingMethod)
    {
        $enabledTypes = $this->methodTypeConfigRepository->findEnabledByMethodIdentifier(
            $shippingMethod->getIdentifier()
        );

        $shippingMethodTypeIdentifiers = array_map(
            function (ShippingMethodTypeInterface $value) {
                return $value->getIdentifier();
            },
            $shippingMethod->getTypes()
        );

        $enabledShippingMethodTypesIdentifiers = array_map(
            function (ShippingMethodTypeConfig $value) {
                return $value->getType();
            },
            $enabledTypes
        );

        $uniqueEnabledShippingMethodTypesIdentifiers = array_unique($enabledShippingMethodTypesIdentifiers);

        $nonDeletableShippingMethodTypes = array_diff(
            $uniqueEnabledShippingMethodTypesIdentifiers,
            $shippingMethodTypeIdentifiers
        );

        return $nonDeletableShippingMethodTypes;
    }
}
