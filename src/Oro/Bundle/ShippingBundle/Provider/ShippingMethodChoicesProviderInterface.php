<?php

namespace Oro\Bundle\ShippingBundle\Provider;

interface ShippingMethodChoicesProviderInterface
{
    /**
     * @param bool $translate
     *
     * @return array
     */
    public function getMethods($translate = false);
}
