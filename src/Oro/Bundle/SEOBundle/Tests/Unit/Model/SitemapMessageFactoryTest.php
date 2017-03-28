<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SEOBundle\Model\SitemapMessageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistry;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Website\WebsiteInterface;

class SitemapMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UrlItemsProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $providerRegistry;

    /**
     * @var WebsiteRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteRepository;

    /**
     * @var SitemapMessageFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->providerRegistry = $this->getMockBuilder(UrlItemsProviderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = new SitemapMessageFactory($this->websiteRepository);
        $this->factory->setProviderRegistry($this->providerRegistry);
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
        
        $jobId = 888;
        /** @var Job $job */
        $job = $this->getEntity(Job::class, ['id' => $jobId]);

        $type = 'some_type';
        $this->providerRegistry->expects($this->once())
            ->method('getProviderNames')
            ->willReturn([$type]);

        $this->expectException(\InvalidArgumentException::class);
        $this->factory->createMessage($website, $type, $version, $job);
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

        $jobId = 888;
        /** @var Job $job */
        $job = $this->getEntity(Job::class, ['id' => $jobId]);

        $type = 'some_type';
        $this->providerRegistry->expects($this->once())
            ->method('getProviderNames')
            ->willReturn([$type]);

        $this->assertEquals(
            [
                SitemapMessageFactory::WEBSITE_ID => $websiteId,
                SitemapMessageFactory::TYPE => $type,
                SitemapMessageFactory::VERSION => $version,
                SitemapMessageFactory::JOB_ID => $jobId,
            ],
            $this->factory->createMessage($website, $type, $version, $job)
        );
    }

    public function testGetWebsiteFromMessage()
    {
        $version = time();
        $websiteId = 777;
        $this->websiteRepository->expects($this->once())
            ->method('checkWebsiteExists')
            ->with($websiteId)
            ->willReturn(true);

        $type = 'some_type';
        $this->providerRegistry->expects($this->once())
            ->method('getProviderNames')
            ->willReturn([$type]);

        $website = $this->createWebsiteMock($websiteId);
        $this->websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $message = [
            SitemapMessageFactory::WEBSITE_ID => $websiteId,
            SitemapMessageFactory::TYPE => $type,
            SitemapMessageFactory::VERSION => $version,
            SitemapMessageFactory::JOB_ID => 1,
        ];
        $this->assertEquals($website, $this->factory->getWebsiteFromMessage($message));
    }

    public function testGetTypeFromMessage()
    {
        $version = time();
        $websiteId = 777;
        $this->websiteRepository->expects($this->once())
            ->method('checkWebsiteExists')
            ->with($websiteId)
            ->willReturn(true);
        $type = 'some_type';
        $this->providerRegistry->expects($this->once())
            ->method('getProviderNames')
            ->willReturn([$type]);

        $message = [
            SitemapMessageFactory::WEBSITE_ID => $websiteId,
            SitemapMessageFactory::TYPE => $type,
            SitemapMessageFactory::VERSION => $version,
            SitemapMessageFactory::JOB_ID => 1,
        ];
        $this->assertEquals($type, $this->factory->getTypeFromMessage($message));
    }

    public function testGetJobIdFromMessage()
    {
        $version = time();
        $websiteId = 777;
        $this->websiteRepository->expects($this->once())
            ->method('checkWebsiteExists')
            ->with($websiteId)
            ->willReturn(true);
        $type = 'some_type';
        $this->providerRegistry->expects($this->once())
            ->method('getProviderNames')
            ->willReturn([$type]);

        $jobId = 888;
        $message = [
            SitemapMessageFactory::WEBSITE_ID => $websiteId,
            SitemapMessageFactory::TYPE => $type,
            SitemapMessageFactory::VERSION => $version,
            SitemapMessageFactory::JOB_ID => $jobId,
        ];
        $this->assertEquals($jobId, $this->factory->getJobIdFromMessage($message));
    }

    public function testGetVersionFromMessage()
    {
        $version = time();
        $websiteId = 777;
        $this->websiteRepository->expects($this->once())
            ->method('checkWebsiteExists')
            ->with($websiteId)
            ->willReturn(true);
        $type = 'some_type';
        $this->providerRegistry->expects($this->once())
            ->method('getProviderNames')
            ->willReturn([$type]);

        $jobId = 888;
        $message = [
            SitemapMessageFactory::WEBSITE_ID => $websiteId,
            SitemapMessageFactory::TYPE => $type,
            SitemapMessageFactory::VERSION => $version,
            SitemapMessageFactory::JOB_ID => $jobId,
        ];
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
