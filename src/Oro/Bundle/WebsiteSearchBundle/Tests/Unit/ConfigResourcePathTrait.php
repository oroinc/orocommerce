<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit;

trait ConfigResourcePathTrait
{
    /**
     * @param string $bundleName
     * @param string $resourceFileName
     * @return string
     */
    protected function getBundleConfigResourcePath($bundleName, $resourceFileName)
    {
        $ds = DIRECTORY_SEPARATOR;
        return __DIR__.$ds.'Fixture'.$ds.'Bundle'.$ds.$bundleName.$ds.'Resources'.$ds.'config'.$ds.$resourceFileName;
    }
}
