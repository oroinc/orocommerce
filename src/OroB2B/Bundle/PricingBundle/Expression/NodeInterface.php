<?php

namespace Oro\Bundle\PricingBundle\Expression;

interface NodeInterface
{
    /**
     * Get current node and all it's subnodes.
     *
     * @return array
     */
    public function getNodes();

    /**
     * @return bool
     */
    public function isBoolean();
}
