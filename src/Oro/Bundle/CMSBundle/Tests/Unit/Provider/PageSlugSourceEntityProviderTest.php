<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\CMSBundle\Provider\PageSlugSourceEntityProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;

class PageSlugSourceEntityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var PageSlugSourceEntityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new PageSlugSourceEntityProvider($this->registry, $this->configManager);
    }

    public function testGetSourceEntityBySlugWhenConfigIsOff()
    {
        $slug = new Slug();
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLE_DIRECT_URL))
            ->willReturn(false);
        $this->assertNull($this->provider->getSourceEntityBySlug($slug));
    }

    public function testGetSourceEntityBySlug()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::ENABLE_DIRECT_URL))
            ->willReturn(true);
        $slug = new Slug();
        $sourceEntity = new Page();
        $repository = $this->createMock(PageRepository::class);
        $repository->expects($this->once())
            ->method('findOneBySlug')
            ->with($slug)
            ->willReturn($sourceEntity);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(Page::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Page::class)
            ->willReturn($manager);
        $this->assertSame($sourceEntity, $this->provider->getSourceEntityBySlug($slug));
    }
}
