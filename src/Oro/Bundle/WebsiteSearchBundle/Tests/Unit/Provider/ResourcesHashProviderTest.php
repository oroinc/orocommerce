<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;

class ResourcesHashProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var string[] */
    protected $tempFiles;

    protected function setUp()
    {
        $this->tempFiles = [];
    }

    protected function tearDown()
    {
        foreach ($this->tempFiles as $tempFile) {
            unlink($tempFile);
        }
    }

    public function testGetHash()
    {
        $resource1 = $this->createResource();
        $resource2 = $this->createResource();

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
    private function createResource()
    {
        return new CumulativeResourceInfo('', '', $this->createTempFile());
    }

    /**
     * Create temp file
     *
     * @return string
     */
    private function createTempFile()
    {
        $fileName = tempnam(sys_get_temp_dir(), 'website-search');
        $this->tempFiles[] = $fileName;

        return $fileName;
    }
}
