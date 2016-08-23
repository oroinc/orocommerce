<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\ConfigResourcePathTrait;
use Oro\Component\Config\CumulativeResourceInfo;

class ResourcesHashProviderTest extends \PHPUnit_Framework_TestCase
{
    use ConfigResourcePathTrait;

    public function testGetHash()
    {
        $pageBundleResource = $this->createResource('TestPageBundle', 'website_search.yml');
        $productBundleResource = $this->createResource('TestProductBundle', 'website_search.yml');

        $resources = [
            $pageBundleResource,
            $productBundleResource
        ];

        $pathsAndTimes = $pageBundleResource->path.filemtime($pageBundleResource->path)
            .'_'.$productBundleResource->path.filemtime($productBundleResource->path);

        $expectedHash = md5($pathsAndTimes);

        $hashProvider = new ResourcesHashProvider();
        $this->assertEquals($expectedHash, $hashProvider->getHash($resources));
    }

    /**
     * @param string $bundle
     * @param string $resourceFile
     * @return CumulativeResourceInfo|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createResource($bundle, $resourceFile)
    {
        $resource = $this->getMockBuilder(CumulativeResourceInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resource->path = $this->getBundleConfigResourcePath($bundle, $resourceFile);

        return $resource;
    }
}
