<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Generator;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Unit\Generator\AbstractCanonicalUrlGeneratorTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use PHPUnit\Framework\MockObject\MockObject;

class CanonicalUrlGeneratorTest extends AbstractCanonicalUrlGeneratorTestCase
{
    private ContentNodeProvider&MockObject $contentNodeProvider;
    private FeatureChecker&MockObject $featureChecker;

    #[\Override]
    protected function createGenerator(): CanonicalUrlGenerator
    {
        $generator = new CanonicalUrlGenerator(
            $this->configManager,
            $this->cache,
            $this->requestStack,
            $this->routingInformationProvider,
            $this->websiteUrlResolver,
            $this->localizationProvider
        );

        $this->contentNodeProvider = $this->createMock(ContentNodeProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $generator->setContentNodeProvider($this->contentNodeProvider);
        $generator->setFeatureChecker($this->featureChecker);
        $generator->addFeature('web_catalog_based_canonical_urls');

        return $generator;
    }

    public function testGetUrlBasedOnWebCatalogNode(): void
    {
        $entity = $this->getSluggableEntity($this->getSlug('/entity'));

        $variant = new ContentVariant();
        $variant->addSlug($this->getSlug('/variant'));

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsiteUrl')
            ->willReturn('http://example.com/');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($entity)
            ->willReturn($variant);

        $this->assertEquals('http://example.com/variant', $this->canonicalUrlGenerator->getUrl($entity));
    }

    public function testGetUrlNoVariantFound(): void
    {
        $entity = $this->getSluggableEntity($this->getSlug('/entity'));

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsiteUrl')
            ->willReturn('http://example.com/');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($entity)
            ->willReturn(null);

        $this->assertUrlTypeCalls(Configuration::INSECURE);
        $this->assertRequestCalls($entity);

        $this->assertEquals('http://example.com/entity', $this->canonicalUrlGenerator->getUrl($entity));
    }

    public function testGetUrlFeatureDisabled(): void
    {
        $entity = $this->getSluggableEntity($this->getSlug('/entity'));

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsiteUrl')
            ->willReturn('http://example.com/');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->contentNodeProvider->expects(self::never())
            ->method('getFirstMatchingVariantForEntity');

        $this->assertUrlTypeCalls(Configuration::INSECURE);
        $this->assertRequestCalls($entity);

        $this->assertEquals('http://example.com/entity', $this->canonicalUrlGenerator->getUrl($entity));
    }

    protected function getSlug(string $url): Slug
    {
        $entitySlug = new Slug();
        $entitySlug->setUrl($url);
        $entitySlug->setRouteName('route_name');
        $entitySlug->setRouteParameters([]);

        return $entitySlug;
    }
}
