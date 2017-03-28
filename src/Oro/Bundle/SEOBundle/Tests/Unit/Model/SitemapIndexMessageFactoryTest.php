<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SEOBundle\Model\SitemapIndexMessageFactory;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Website\WebsiteInterface;

class SitemapIndexMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var WebsiteRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteRepository;

    /**
     * @var SitemapIndexMessageFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = new SitemapIndexMessageFactory($this->websiteRepository);
    }

    public function testCreateMessageWithException()
    {
        $version = time();
        $websiteId = 777;
        $website = $this->createWebsiteMock($websiteId);
        $this->websiteRepository->expects($this->once())
            ->method('checkWebsiteExists')
            ->with($websiteId)
            ->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->factory->createMessage($website, $version);
    }

    public function testCreateMessage()
    {
        $version = time();
        $websiteId = 777;
        $website = $this->createWebsiteMock($websiteId);
        $this->websiteRepository->expects($this->once())
            ->method('checkWebsiteExists')
            ->with($websiteId)
            ->willReturn(true);

        $this->assertEquals(
            [
                SitemapIndexMessageFactory::WEBSITE_ID => $websiteId,
                SitemapIndexMessageFactory::VERSION => $version,
            ],
            $this->factory->createMessage($website, $version)
        );
    }

    public function testGetWebsiteFromMessage()
    {
        $version = time();
        $websiteId = 777;
        $message = [
            SitemapIndexMessageFactory::WEBSITE_ID => $websiteId,
            SitemapIndexMessageFactory::VERSION => $version,
        ];
        $this->websiteRepository->expects($this->once())
            ->method('checkWebsiteExists')
            ->with($websiteId)
            ->willReturn(true);

        $website = $this->createWebsiteMock($websiteId);
        $this->websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $this->assertEquals($website, $this->factory->getWebsiteFromMessage($message));
    }

    public function testGetVersionFromMessage()
    {
        $version = time();
        $websiteId = 777;
        $message = [
            SitemapIndexMessageFactory::WEBSITE_ID => $websiteId,
            SitemapIndexMessageFactory::VERSION => $version,
        ];
        $this->websiteRepository->expects($this->once())
            ->method('checkWebsiteExists')
            ->with($websiteId)
            ->willReturn(true);

        $this->assertEquals($version, $this->factory->getVersionFromMessage($message));
    }

    /**
     * @param int $websiteId
     * @return WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createWebsiteMock($websiteId)
    {
        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        return $website;
    }
}
