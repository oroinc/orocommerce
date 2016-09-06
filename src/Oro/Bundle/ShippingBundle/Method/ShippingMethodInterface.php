<?php

namespace Oro\Bundle\ShippingBundle\Method;

interface ShippingMethodInterface
{
    /**
     * @return bool
     */
    public function isGrouped();

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getLabel();

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
     * @return array
     */
    public function getOptions();

    /**
     * @return int
     */
    public function getSortOrder();
}
