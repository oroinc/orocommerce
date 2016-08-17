<?php

namespace Oro\Bundle\WebsiteSearchBundle\Loader;

use Oro\Component\Config\CumulativeResourceInfo;

interface ConfigurationLoaderInterface
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
