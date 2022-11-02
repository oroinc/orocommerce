<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SluggableEntityFinder;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\ContentVariantSlugSourceEntityProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class ContentVariantSlugSourceEntityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SluggableEntityFinder|\PHPUnit\Framework\MockObject\MockObject */
    private $sluggableEntityFinder;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ContentVariantSlugSourceEntityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->sluggableEntityFinder = $this->createMock(SluggableEntityFinder::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new ContentVariantSlugSourceEntityProvider(
            $this->sluggableEntityFinder,
            $this->websiteManager
        );
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
        $this->sluggableEntityFinder->expects($this->never())
            ->method('findEntityBySlug');
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
        $this->sluggableEntityFinder->expects($this->never())
            ->method('findEntityBySlug');
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
        $this->sluggableEntityFinder->expects($this->once())
            ->method('findEntityBySlug')
            ->with(ContentVariant::class, $this->identicalTo($slug))
            ->willReturn($sourceEntity);
        $this->assertSame($sourceEntity, $this->provider->getSourceEntityBySlug($slug));
    }
}
