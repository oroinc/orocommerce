<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Generator;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Generator\BatchCanonicalUrlGeneratorInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Generator\BatchCanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BatchCanonicalUrlGeneratorTest extends TestCase
{
    private const string FEATURE = 'web_catalog_based_canonical_urls';

    private BatchCanonicalUrlGeneratorInterface&MockObject $innerGenerator;
    private ContentNodeProvider&MockObject $contentNodeProvider;
    private FeatureChecker&MockObject $featureChecker;
    private BatchCanonicalUrlGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerGenerator = $this->createMock(BatchCanonicalUrlGeneratorInterface::class);
        $this->contentNodeProvider = $this->createMock(ContentNodeProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->generator = new BatchCanonicalUrlGenerator($this->innerGenerator, $this->contentNodeProvider);
        $this->generator->setFeatureChecker($this->featureChecker);
        $this->generator->addFeature(self::FEATURE);
    }

    public function testGetUrlsWithoutIds(): void
    {
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');
        $this->contentNodeProvider->expects(self::never())
            ->method('getFirstMatchingVariantIdsForEntities');
        $this->innerGenerator->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->generator->getUrls(Page::class, []));
    }

    public function testGetUrlsWhenFeatureIsDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(false);
        $this->contentNodeProvider->expects(self::never())
            ->method('getFirstMatchingVariantIdsForEntities');
        $this->innerGenerator->expects(self::once())
            ->method('getUrls')
            ->with(Page::class, [1, 2], null, null)
            ->willReturn([1 => 'http://example.com/page-1', 2 => 'http://example.com/page-2']);

        self::assertSame(
            [1 => 'http://example.com/page-1', 2 => 'http://example.com/page-2'],
            $this->generator->getUrls(Page::class, [1, 2])
        );
    }

    public function testGetUrlsWhenNoVariantIsFound(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);
        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantIdsForEntities')
            ->with(Page::class, [1, 2], null)
            ->willReturn([]);
        $this->innerGenerator->expects(self::never())
            ->method('getDirectUrls');
        $this->innerGenerator->expects(self::once())
            ->method('getUrls')
            ->with(Page::class, [1, 2], null, null)
            ->willReturn([1 => 'http://example.com/page-1', 2 => 'http://example.com/page-2']);

        self::assertSame(
            [1 => 'http://example.com/page-1', 2 => 'http://example.com/page-2'],
            $this->generator->getUrls(Page::class, [1, 2])
        );
    }

    public function testGetUrlsFromVariantsOnly(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 7);
        $localization = new Localization();
        ReflectionUtil::setId($localization, 42);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);
        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantIdsForEntities')
            ->with(Page::class, [1, 2], $website)
            ->willReturn([1 => 10, 2 => 20]);
        $this->innerGenerator->expects(self::once())
            ->method('getDirectUrls')
            ->with(ContentVariant::class, [10, 20], $localization, $website)
            ->willReturn([10 => 'http://example.com/variant-10', 20 => 'http://example.com/variant-20']);
        $this->innerGenerator->expects(self::never())
            ->method('getUrls');

        self::assertSame(
            [1 => 'http://example.com/variant-10', 2 => 'http://example.com/variant-20'],
            $this->generator->getUrls(Page::class, [1, 2], $localization, $website)
        );
    }

    public function testGetUrlsFromVariantsAndFromTheDecoratedGenerator(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);
        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantIdsForEntities')
            ->with(Page::class, [1, 2, 3], null)
            ->willReturn([2 => 20]);
        $this->innerGenerator->expects(self::once())
            ->method('getDirectUrls')
            ->with(ContentVariant::class, [20], null, null)
            ->willReturn([20 => 'http://example.com/variant-20']);
        // only the entities without a usable variant are delegated
        $this->innerGenerator->expects(self::once())
            ->method('getUrls')
            ->with(Page::class, [1, 3], null, null)
            ->willReturn([1 => 'http://example.com/page-1', 3 => 'http://example.com/page-3']);

        // the order of the returned URLs follows the order of the requested identifiers
        self::assertSame(
            [
                1 => 'http://example.com/page-1',
                2 => 'http://example.com/variant-20',
                3 => 'http://example.com/page-3',
            ],
            $this->generator->getUrls(Page::class, [1, 2, 3])
        );
    }

    public function testGetUrlsWhenVariantHasNoSlug(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE)
            ->willReturn(true);
        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantIdsForEntities')
            ->with(Page::class, [1], null)
            ->willReturn([1 => 10]);
        $this->innerGenerator->expects(self::once())
            ->method('getDirectUrls')
            ->with(ContentVariant::class, [10], null, null)
            ->willReturn([]);
        $this->innerGenerator->expects(self::once())
            ->method('getUrls')
            ->with(Page::class, [1], null, null)
            ->willReturn([1 => '/system/1']);

        self::assertSame([1 => '/system/1'], $this->generator->getUrls(Page::class, [1]));
    }

    public function testGetDirectUrlsIsDelegated(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 7);
        $localization = new Localization();
        ReflectionUtil::setId($localization, 42);

        // the web catalog is not involved, a content variant is not resolved for a content variant
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');
        $this->contentNodeProvider->expects(self::never())
            ->method('getFirstMatchingVariantIdsForEntities');
        $this->innerGenerator->expects(self::once())
            ->method('getDirectUrls')
            ->with(Page::class, [1], $localization, $website)
            ->willReturn([1 => 'http://example.com/page-1']);

        self::assertSame(
            [1 => 'http://example.com/page-1'],
            $this->generator->getDirectUrls(Page::class, [1], $localization, $website)
        );
    }
}
