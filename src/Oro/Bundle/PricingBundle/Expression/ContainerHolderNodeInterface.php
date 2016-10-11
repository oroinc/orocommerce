<?php

namespace Oro\Bundle\PricingBundle\Expression;

interface ContainerHolderNodeInterface
{
    /**
     * @return string
     */
    public function getContainer();

    /**
     * @return string
     */
    public function getResolvedContainer();

    /**
     * @return int|null|string
     */
    public function getContainerId();
}
