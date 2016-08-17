<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Component\Config\CumulativeResourceInfo;

interface ResourcesBasedConfigurationProviderInterface
{
    /**
     * Returns yml file resources which define configuration.
     * @return CumulativeResourceInfo[]
     */
    public function getResources();

    /**
     * @return array
     */
    public function getConfiguration();
}
