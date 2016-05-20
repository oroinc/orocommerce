<?php

namespace OroB2B\Bundle\ProductBundle\Entity;

/**
 * Interface should be used for all models which are used to specify the units of measurement
 */
interface MeasureUnitInterface
{
    /**
     * @return string
     */
    public function getCode();
}
