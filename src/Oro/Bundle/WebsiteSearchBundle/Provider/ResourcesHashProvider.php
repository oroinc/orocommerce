<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Component\Config\CumulativeResourceInfo;

class ResourcesHashProvider
{
    /**
     * @param CumulativeResourceInfo[] $resources
     * @return string
     */
    public function getHash($resources)
    {
        $modificationTimes = [];
        foreach ($resources as $resource) {
            $modificationTimes[] = $resource->path.filemtime($resource->path);
        }

        return md5(implode('_', $modificationTimes));
    }
}
