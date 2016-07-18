<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

interface NodeInterface
{
    /**
     * Get current node and all it's subnodes.
     *
     * @return array|null
     */
    public function getNodes();

    /**
     * @return bool
     */
    public function isBoolean();
}
