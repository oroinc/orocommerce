<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Provider\ContentVariantUrlItemsProvider;
use Oro\Bundle\SEOBundle\Sitemap\Provider\WebCatalogScopeCriteriaProvider;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\Website\WebsiteInterface;

class ContentVariantUrlItemsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var WebCatalogProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webCatalogProvider;

    /**
     * @var ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentNodeTreeResolver;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var WebCatalogScopeCriteriaProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeCriteriaProvider;

    /**
     * @var ContentVariantUrlItemsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->webCatalogProvider = $this->createMock(WebCatalogProvider::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->scopeCriteriaProvider = $this->createMock(WebCatalogScopeCriteriaProvider::class);

        $this->provider = new ContentVariantUrlItemsProvider(
            $this->registry,
            $this->webCatalogProvider,
            $this->contentNodeTreeResolver,
            $this->canonicalUrlGenerator,
            $this->scopeCriteriaProvider
        );
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature('test_feature');
    }

    public function testGetUrlItemsWhenFeatureIsDisabled()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test_feature', $website)
            ->willReturn(true);
        $items = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertEmpty($items);
    }

    public function testGetUrlItemsWhenWebCatalogNotFound()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test_feature', $website)
            ->willReturn(false);
        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->with($website)
            ->willReturn(null);

        $items = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertEmpty($items);
    }

    public function testGetUrlItemsWhenWebCatalogHasNoRoot()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;
        $webCatalog = $this->createMock(WebCatalog::class);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test_feature', $website)
            ->willReturn(false);
        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->with($website)
            ->willReturn($webCatalog);

        $contentNodeRepo = $this->createMock(ContentNodeRepository::class);
        $contentNodeRepo->expects($this->once())
            ->method('getRootNodeByWebCatalog')
            ->with($webCatalog)
            ->willReturn(null);
        $contentNodeEm = $this->createMock(EntityManagerInterface::class);
        $contentNodeEm->expects($this->once())
            ->method('getRepository')
            ->willReturn($contentNodeRepo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($contentNodeEm);

        $items = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertEmpty($items);
    }

    public function testGetUrlItemsWhenThereIsNoResolvedNode()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;
        $webCatalog = $this->createMock(WebCatalog::class);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test_feature', $website)
            ->willReturn(false);
        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->with($website)
            ->willReturn($webCatalog);

        $rootNode = $this->createMock(ContentNode::class);
        $contentNodeRepo = $this->createMock(ContentNodeRepository::class);
        $contentNodeRepo->expects($this->once())
            ->method('getRootNodeByWebCatalog')
            ->with($webCatalog)
            ->willReturn($rootNode);
        $contentNodeEm = $this->createMock(EntityManagerInterface::class);
        $contentNodeEm->expects($this->once())
            ->method('getRepository')
            ->willReturn($contentNodeRepo);
        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeCriteriaProvider->expects($this->once())
            ->method('getWebCatalogScopeForAnonymousCustomerGroup')
            ->with($website)
            ->willReturn($scopeCriteria);

        $scope = $this->createMock(Scope::class);
        $slugRepo = $this->createMock(SlugRepository::class);
        $slugRepo->expects($this->once())
            ->method('findMostSuitableUsedScope')
            ->with($scopeCriteria)
            ->willReturn($scope);
        $slugEm = $this->createMock(EntityManagerInterface::class);
        $slugEm->expects($this->once())
            ->method('getRepository')
            ->willReturn($slugRepo);

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive(
                [ContentNode::class],
                [Slug::class]
            )
            ->willReturn(
                $contentNodeEm,
                $slugEm
            );
        $this->contentNodeTreeResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($rootNode, $scope)
            ->willReturn(null);

        $items = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertEmpty($items);
    }

    public function testGetUrlItemsWhenScopeNotExistsForAnonymousCustomerGroup(): void
    {
        $version = 1;
        $website = $this->createMock(WebsiteInterface::class);
        $webCatalog = $this->createMock(WebCatalog::class);

        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test_feature', $website)
            ->willReturn(false);

        $this->webCatalogProvider
            ->expects($this->once())
            ->method('getWebCatalog')
            ->with($website)
            ->willReturn($webCatalog);

        $rootNode = $this->createMock(ContentNode::class);
        $contentNodeRepo = $this->createMock(ContentNodeRepository::class);
        $contentNodeRepo
            ->expects($this->once())
            ->method('getRootNodeByWebCatalog')
            ->with($webCatalog)
            ->willReturn($rootNode);

        $contentNodeEm = $this->createMock(EntityManagerInterface::class);
        $contentNodeEm
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($contentNodeRepo);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeCriteriaProvider
            ->expects($this->once())
            ->method('getWebCatalogScopeForAnonymousCustomerGroup')
            ->with($website)
            ->willReturn($scopeCriteria);

        $slugRepo = $this->createMock(SlugRepository::class);
        $slugRepo
            ->expects($this->once())
            ->method('findMostSuitableUsedScope')
            ->with($scopeCriteria)
            ->willReturn(null);

        $slugEm = $this->createMock(EntityManagerInterface::class);
        $slugEm
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($slugRepo);

        $this->registry
            ->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive([ContentNode::class], [Slug::class])
            ->willReturn($contentNodeEm, $slugEm);

        $this->contentNodeTreeResolver
            ->expects($this->never())
            ->method('getResolvedContentNode');

        $items = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertEmpty($items);
    }

    public function testGetUrlItems()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;
        $webCatalog = $this->createMock(WebCatalog::class);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test_feature', $website)
            ->willReturn(false);
        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->with($website)
            ->willReturn($webCatalog);
        $rootNode = $this->createMock(ContentNode::class);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeCriteriaProvider->expects($this->once())
            ->method('getWebCatalogScopeForAnonymousCustomerGroup')
            ->with($website)
            ->willReturn($scopeCriteria);

        $contentNodeRepo = $this->createMock(ContentNodeRepository::class);
        $contentNodeRepo->expects($this->once())
            ->method('getRootNodeByWebCatalog')
            ->with($webCatalog)
            ->willReturn($rootNode);
        $contentNodeEm = $this->createMock(EntityManagerInterface::class);
        $contentNodeEm->expects($this->once())
            ->method('getRepository')
            ->willReturn($contentNodeRepo);

        $scope = $this->createMock(Scope::class);
        $slugRepo = $this->createMock(SlugRepository::class);
        $slugRepo->expects($this->once())
            ->method('findMostSuitableUsedScope')
            ->with($scopeCriteria)
            ->willReturn($scope);
        $slugEm = $this->createMock(EntityManagerInterface::class);
        $slugEm->expects($this->once())
            ->method('getRepository')
            ->willReturn($slugRepo);

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive(
                [ContentNode::class],
                [Slug::class]
            )
            ->willReturn(
                $contentNodeEm,
                $slugEm
            );

        $variant = new ResolvedContentVariant();
        $variant->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test1'));
        $variant->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test2'));
        $resolvedContentNode = new ResolvedContentNode(1, 1, new ArrayCollection(), $variant);

        $childVariant = new ResolvedContentVariant();
        $childVariant->addLocalizedUrl((new LocalizedFallbackValue())->setString('/test1/child'));
        $resolvedChildContentNode = new ResolvedContentNode(2, 2, new ArrayCollection(), $childVariant);

        $resolvedContentNode->addChildNode($resolvedChildContentNode);

        $this->contentNodeTreeResolver->expects($this->once())
            ->method('getResolvedContentNode')
            ->with($rootNode, $scope)
            ->willReturn($resolvedContentNode);

        $this->canonicalUrlGenerator->expects($this->exactly(3))
            ->method('getAbsoluteUrl')
            ->willReturnArgument(0);

        $items = iterator_to_array($this->provider->getUrlItems($website, $version));
        $expected = [
            new UrlItem('/test1'),
            new UrlItem('/test2'),
            new UrlItem('/test1/child'),
        ];
        $this->assertEquals($expected, $items);
    }
}
