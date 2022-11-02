<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * An interface for shipping method type.
 */
interface ShippingMethodInterface
{
    /**
     * @return bool
     */
    public function isGrouped();

    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @return ShippingMethodTypeInterface[]
     */
    public function getTypes();

    /**
     * @param string $identifier
     * @return ShippingMethodTypeInterface|null
     */
    public function getType($identifier);

    /**
     * @return string
     */
    public function getOptionsConfigurationFormType();

    /**
     * @return int
     */
    public function getSortOrder();
}
