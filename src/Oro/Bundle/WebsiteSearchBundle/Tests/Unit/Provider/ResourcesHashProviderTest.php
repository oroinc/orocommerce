<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Testing\TempDirExtension;

class ResourcesHashProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    public function testGetHash()
    {
        $tempDir = $this->getTempDir('website-search');
        $resource1 = $this->createResource($tempDir);
        $resource2 = $this->createResource($tempDir);

        $pathsAndTimes = sprintf(
            '%s%d_%s%d',
            $resource1->path,
            filemtime($resource1->path),
            $resource2->path,
            filemtime($resource2->path)
        );

        $expectedHash = md5($pathsAndTimes);

        $hashProvider = new ResourcesHashProvider();
        $this->assertEquals($expectedHash, $hashProvider->getHash([$resource1, $resource2]));
    }

    /**
     * @return CumulativeResourceInfo
     */
    private function createResource(string $tempDir)
    {
        return new CumulativeResourceInfo('', '', tempnam($tempDir, 'resource'));
    }
}
