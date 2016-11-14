<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class WebsiteContextManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelperMock;

    /** @var  WebsiteContextManager */
    private $websiteContextManager;

    /** @var WebsiteRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $websiteRepositoryMock;

    protected function setUp()
    {
        $this->doctrineHelperMock = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteRepositoryMock = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelperMock
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(Website::class)
            ->willReturn($this->websiteRepositoryMock);

        $this->websiteContextManager = new WebsiteContextManager($this->doctrineHelperMock);
    }

    public function testGetWebsiteIdExists()
    {
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = 777;
        $this->websiteRepositoryMock
            ->expects($this->once())
            ->method('checkWebsiteExists')
            ->with(777)
            ->willReturn([777]);
        $this->assertEquals(777, $this->websiteContextManager->getWebsiteId($context));
    }

    public function testGetWebsiteIdNotExist()
    {
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = 777;
        $this->websiteRepositoryMock
            ->expects($this->once())
            ->method('checkWebsiteExists')
            ->with(777)
            ->willReturn([]);
        $this->assertNull($this->websiteContextManager->getWebsiteId($context));
    }
}
