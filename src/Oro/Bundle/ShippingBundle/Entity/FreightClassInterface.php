<?php

namespace Oro\Bundle\ShippingBundle\Entity;

/**
 * Defines the contract for freight class entities.
 *
 * Freight classes are used to categorize shipments based on their handling characteristics,
 * which affects shipping costs and carrier requirements.
 */
interface FreightClassInterface
{
    /**
     * @return string
     */
    public function getCode();
}
