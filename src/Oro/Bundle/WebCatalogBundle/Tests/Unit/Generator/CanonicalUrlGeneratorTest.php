<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Generator;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator as BaseGenerator;
use Oro\Bundle\RedirectBundle\Tests\Unit\Generator\AbstractCanonicalUrlGeneratorTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;

class CanonicalUrlGeneratorTest extends AbstractCanonicalUrlGeneratorTestCase
{
    /**
     * @var ContentNodeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentNodeProvider;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    /**
     * {@inheritDoc}
     */
    protected function createGenerator(): BaseGenerator
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

    public function testGetUrlBasedOnWebCatalogNode()
    {
        $entity = $this->getSluggableEntity($this->getSlug('/entity'));

        $variant = new ContentVariant();
        $variant->addSlug($this->getSlug('/variant'));

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsiteUrl')
            ->willReturn('http://example.com/');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->contentNodeProvider->expects($this->once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($entity)
            ->willReturn($variant);

        $this->assertEquals('http://example.com/variant', $this->canonicalUrlGenerator->getUrl($entity));
    }

    public function testGetUrlNoVariantFound()
    {
        $entity = $this->getSluggableEntity($this->getSlug('/entity'));

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsiteUrl')
            ->willReturn('http://example.com/');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->contentNodeProvider->expects($this->once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($entity)
            ->willReturn(null);

        $this->assertUrlTypeCalls(Configuration::INSECURE);
        $this->assertRequestCalls($entity);

        $this->assertEquals('http://example.com/entity', $this->canonicalUrlGenerator->getUrl($entity));
    }

    public function testGetUrlFeatureDisabled()
    {
        $entity = $this->getSluggableEntity($this->getSlug('/entity'));

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsiteUrl')
            ->willReturn('http://example.com/');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->contentNodeProvider->expects($this->never())
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
