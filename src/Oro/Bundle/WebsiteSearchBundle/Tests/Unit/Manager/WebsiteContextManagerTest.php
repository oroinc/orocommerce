<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteContextManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const WEBSITE_ID = 777;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelperMock;

    /** @var  WebsiteContextManager */
    private $websiteContextManager;

    /** @var WebsiteRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteRepositoryMock;

    /** @var Website */
    private $website;

    protected function setUp(): void
    {
        $this->websiteRepositoryMock = $this->createMock(WebsiteRepository::class);
        $this->doctrineHelperMock = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelperMock
            ->expects($this->any())
            ->method('getEntityRepository')
            ->with(Website::class)
            ->willReturn($this->websiteRepositoryMock);

        $this->website = $this->getEntity(Website::class, [ 'id' => self::WEBSITE_ID ]);
        $this->websiteContextManager = new WebsiteContextManager($this->doctrineHelperMock);
    }

    protected function tearDown(): void
    {
        unset($this->doctrineHelperMock);
        unset($this->websiteRepositoryMock);
        unset($this->website);
        unset($this->websiteContextManager);
    }

    public function testGetWebsiteIdExists()
    {
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = self::WEBSITE_ID;
        $this->websiteRepositoryMock
            ->expects($this->once())
            ->method('checkWebsiteExists')
            ->with(self::WEBSITE_ID)
            ->willReturn(true);

        $this->assertEquals(self::WEBSITE_ID, $this->websiteContextManager->getWebsiteId($context));
    }

    public function testGetWebsiteIdNotExist()
    {
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = self::WEBSITE_ID;
        $this->websiteRepositoryMock
            ->expects($this->once())
            ->method('checkWebsiteExists')
            ->with(self::WEBSITE_ID)
            ->willReturn(false);

        $this->assertNull($this->websiteContextManager->getWebsiteId($context));
    }

    public function testGetWebsiteExists()
    {
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = self::WEBSITE_ID;
        $this->websiteRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(self::WEBSITE_ID)
            ->willReturn($this->website);

        $this->assertEquals($this->website, $this->websiteContextManager->getWebsite($context));
    }

    public function testGetWebsiteNotExists()
    {
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = self::WEBSITE_ID;
        $this->websiteRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(self::WEBSITE_ID)
            ->willReturn(null);

        $this->assertNull($this->websiteContextManager->getWebsite($context));
    }
}
