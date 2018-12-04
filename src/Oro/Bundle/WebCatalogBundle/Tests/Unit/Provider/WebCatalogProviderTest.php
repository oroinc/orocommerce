<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Website\WebsiteInterface;

class WebCatalogProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var WebCatalogProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new WebCatalogProvider($this->registry, $this->configManager);
    }

    public function testGetWebCatalogWithoutWebsite()
    {
        $website = null;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn(1);

        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->createMock(WebCatalog::class);
        $this->assertDatabaseSearchCall($webCatalog);

        $this->assertEquals($webCatalog, $this->provider->getWebCatalog());
    }

    public function testGetWebCatalogWithWebsite()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn(1);

        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->createMock(WebCatalog::class);
        $this->assertDatabaseSearchCall($webCatalog);

        $this->assertEquals($webCatalog, $this->provider->getWebCatalog($website));
    }

    public function testGetNavigationRootWithoutWebsite()
    {
        $website = null;
        $rootContentNodeId = 2;
        $webCatalogId = 1;

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn($webCatalogId);

        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_web_catalog.navigation_root', false, false, $website)
            ->willReturn($rootContentNodeId);

        /** @var WebCatalog $webCatalogNode */
        $webCatalogNode = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);

        /** @var  ContentNode $navigationRootNode */
        $navigationRootNode = $this->getEntity(ContentNode::class, ['id' => $rootContentNodeId]);

        $navigationRootNode->setWebCatalog($webCatalogNode);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(ContentNode::class, $rootContentNodeId)
            ->willReturn($navigationRootNode);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('find')
            ->with(ContentNode::class, $rootContentNodeId)
            ->willReturn($navigationRootNode);

        $this->assertEquals($navigationRootNode, $this->provider->getNavigationRoot());
    }

    public function testGetNavigationRootWithWebsite()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $rootContentNodeId = 2;
        $webCatalogId = 1;

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn($webCatalogId);

        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_web_catalog.navigation_root', false, false, $website)
            ->willReturn($rootContentNodeId);

        /** @var WebCatalog $webCatalogNode */
        $webCatalogNode = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);

        /** @var  ContentNode $navigationRootNode */
        $navigationRootNode = $this->getEntity(ContentNode::class, ['id' => $rootContentNodeId]);

        $navigationRootNode->setWebCatalog($webCatalogNode);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(ContentNode::class, $rootContentNodeId)
            ->willReturn($navigationRootNode);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('find')
            ->with(ContentNode::class, $rootContentNodeId)
            ->willReturn($navigationRootNode);

        $this->assertEquals($navigationRootNode, $this->provider->getNavigationRoot($website));
    }

    public function testGetNavigationRootWithRemovedContentNode(): void
    {
        $website = null;
        $rootContentNodeId = 2;
        $webCatalogId = 1;

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn($webCatalogId);

        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_web_catalog.navigation_root', false, false, $website)
            ->willReturn($rootContentNodeId);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(ContentNode::class, $rootContentNodeId)
            ->willReturn(null);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->assertNull($this->provider->getNavigationRoot());
    }

    /**
     * @param WebCatalog $webCatalog
     */
    private function assertDatabaseSearchCall(WebCatalog $webCatalog)
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(WebCatalog::class, 1)
            ->willReturn($webCatalog);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(WebCatalog::class)
            ->willReturn($em);
    }
}
