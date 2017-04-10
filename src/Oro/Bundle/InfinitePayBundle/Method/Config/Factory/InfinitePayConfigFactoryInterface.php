<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Config\Factory;

use Oro\Bundle\InfinitePayBundle\Entity\InfinitePaySettings;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;

interface InfinitePayConfigFactoryInterface
{
    /**
     * @param InfinitePaySettings $settings
     * @return InfinitePayConfigInterface
     */
    public function createConfig(InfinitePaySettings $settings);
}
