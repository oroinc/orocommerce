<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Provider\SitemapUrlProviderRegistry;
use Oro\Bundle\SEOBundle\Tools\SitemapDumper;
use Oro\Component\SEO\Provider\SitemapUrlProviderInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Filesystem\Filesystem;

class SitemapDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SitemapUrlProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $providerRegistry;

    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;

    /**
     * @var string
     */
    private $rootFolder;

    /**
     * @var SitemapDumper
     */
    private $dumper;

    protected function setUp()
    {
        $this->providerRegistry = $this->getMockBuilder(SitemapUrlProviderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->rootFolder = sys_get_temp_dir();
        $this->dumper = new SitemapDumper($this->providerRegistry, $this->filesystem, $this->rootFolder);
    }

    public function testDump()
    {
        /** @var WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->createMock(WebsiteInterface::class);
        $websiteId = 1;
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        $providerName = 'name';
        $provider = $this->createMock(SitemapUrlProviderInterface::class);
        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([$providerName => $provider]);
        $provider->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([
                new UrlItem('http://example.com/1', 'daily', 0.5, new \DateTime()),
                new UrlItem('http://example.com/2', 'daily', 0.5, new \DateTime()),
            ]);
        
        $this->filesystem->expects($this->exactly(2))
            ->method('remove');
        $this->filesystem->expects($this->once())
            ->method('copy');
        $this->filesystem->expects($this->once())
            ->method('dumpFile');

        $this->dumper->dump($website);
    }
}
