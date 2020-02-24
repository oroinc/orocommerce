<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Provider\ContentVariantSlugSourceEntityProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class ContentVariantSlugSourceEntityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ContentVariantSlugSourceEntityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->provider = new ContentVariantSlugSourceEntityProvider($this->registry, $this->websiteManager);
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature('frontend_master_catalog');
    }

    public function testGetSourceEntityBySlugWhenNoWebsite()
    {
        $slug = new Slug();
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->assertNull($this->provider->getSourceEntityBySlug($slug));
    }

    public function testGetSourceEntityBySlugWhenFeatureIsEnabled()
    {
        $slug = new Slug();
        $website = new Website();
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('frontend_master_catalog', $website)
            ->willReturn(true);
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->assertNull($this->provider->getSourceEntityBySlug($slug));
    }

    public function testGetSourceEntityBySlug()
    {
        $slug = new Slug();
        $website = new Website();
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('frontend_master_catalog', $website)
            ->willReturn(false);
        $sourceEntity = new ContentVariant();
        $repository = $this->createMock(ContentVariantRepository::class);
        $repository->expects($this->once())
            ->method('findOneBySlug')
            ->with($slug)
            ->willReturn($sourceEntity);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(ContentVariant::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentVariant::class)
            ->willReturn($manager);
        $this->assertSame($sourceEntity, $this->provider->getSourceEntityBySlug($slug));
    }
}
