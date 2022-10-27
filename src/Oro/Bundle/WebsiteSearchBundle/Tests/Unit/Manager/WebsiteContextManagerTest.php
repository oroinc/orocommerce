<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class WebsiteContextManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var WebsiteContextManager */
    private $websiteContextManager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->websiteContextManager = new WebsiteContextManager($this->doctrine);
    }

    public function testGetWebsiteIdExists(): void
    {
        $websiteId = 123;
        $website = $this->createMock(Website::class);
        $website->expects(self::once())
            ->method('getId')
            ->willReturn($websiteId);
        $context = [AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        self::assertSame($websiteId, $this->websiteContextManager->getWebsiteId($context));
    }

    public function testGetWebsiteIdNotExist(): void
    {
        $websiteId = 123;
        $context = [AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn(null);

        self::assertNull($this->websiteContextManager->getWebsiteId($context));
    }

    public function testGetWebsiteIdNoWebsiteIdInContext(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertNull($this->websiteContextManager->getWebsiteId([]));
    }

    public function testGetWebsiteExists(): void
    {
        $websiteId = 123;
        $website = $this->createMock(Website::class);
        $context = [AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        self::assertSame($website, $this->websiteContextManager->getWebsite($context));
    }

    public function testGetWebsiteNotExists(): void
    {
        $websiteId = 123;
        $context = [AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn(null);

        self::assertNull($this->websiteContextManager->getWebsite($context));
    }

    public function testGetWebsiteNoWebsiteIdInContext(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertNull($this->websiteContextManager->getWebsite([]));
    }
}
