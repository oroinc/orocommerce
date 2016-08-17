<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

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
        $dirName = dirname(__DIR__);
        return $dirName.$ds.'Fixture'.$ds.'Bundle'.$ds.$bundleName.$ds.'Resources'.$ds.'config'.$ds.$resourceFileName;
    }
}
