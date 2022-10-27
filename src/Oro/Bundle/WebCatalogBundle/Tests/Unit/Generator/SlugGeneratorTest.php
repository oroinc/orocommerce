<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\RedirectGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Resolver\UniqueContentNodeSlugPrototypesResolver;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SlugGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContentVariantTypeRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contentVariantTypeRegistry;

    /**
     * @var RedirectGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $redirectGenerator;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $localizationHelper;

    /**
     * @var SlugGenerator
     */
    protected $slugGenerator;

    /**
     * @var SlugUrlDiffer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $slugUrlDiffer;

    /**
     * @var UniqueContentNodeSlugPrototypesResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $uniqueSlugPrototypesResolver;

    protected function setUp(): void
    {
        $this->contentVariantTypeRegistry = $this->createMock(ContentVariantTypeRegistry::class);
        $this->redirectGenerator = $this->createMock(RedirectGenerator::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->slugUrlDiffer = $this->createMock(SlugUrlDiffer::class);
        $this->uniqueSlugPrototypesResolver = $this->createMock(UniqueContentNodeSlugPrototypesResolver::class);

        $this->slugGenerator = new SlugGenerator(
            $this->contentVariantTypeRegistry,
            $this->redirectGenerator,
            $this->localizationHelper,
            $this->slugUrlDiffer,
            $this->uniqueSlugPrototypesResolver
        );
    }

    public function testGenerateForRoot()
    {
        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $contentVariantType = $this->createMock(ContentVariantTypeInterface::class);
        $contentVariantType->expects($this->once())
            ->method('getRouteData')
            ->willReturn($routData);

        $contentVariant = $this->createContentVariant($scope, 'test_type');

        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);
        $contentNode->addContentVariant($contentVariant);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->willReturn($contentVariantType);

        $this->slugGenerator->generate($contentNode);

        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();
        $actualSlugs = $actualContentVariant->getSlugs();

        $this->assertCount(1, $actualSlugs);
        $expectedSlug = (new Slug())
            ->setUrl(SlugGenerator::ROOT_URL)
            ->setRouteName($routeId)
            ->setRouteParameters($routeParameters)
            ->addScope($scope)
            ->setOrganization($organization);

        $this->assertCount(1, $contentNode->getLocalizedUrls());
        $expectedUrl = (new LocalizedFallbackValue())->setText(SlugGenerator::ROOT_URL);
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertEquals($expectedUrl, $url);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertEquals($expectedSlug, $slug);
        }
    }

    public function testGenerate()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test_localization']);

        $parentNodeSlugUrl = '/parent/node';
        $parentSlug = new Slug();
        $parentSlug->setUrl($parentNodeSlugUrl);
        $parentSlug->setLocalization($localization);

        $parentContentVariant = new ContentVariant();
        $parentContentVariant->addSlug($parentSlug);

        $parentContentNode = new ContentNode();
        $parentContentNode->setWebCatalog($webCatalog);
        $localizedUrl = (new LocalizedFallbackValue())->setText($parentNodeSlugUrl);
        $parentContentNode->addLocalizedUrl($localizedUrl);
        $parentContentNode->addContentVariant($parentContentVariant);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizedValue')
            ->with($parentContentNode->getLocalizedUrls(), $localization)
            ->willReturn($localizedUrl);

        $this->doTestGenerate($localization, $parentContentNode);
    }

    public function testGenerateEmptyPrototype()
    {
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test_localization']);

        $rootUrl = '/';
        $parentSlug = new Slug();
        $parentSlug->setUrl($rootUrl);
        $parentSlug->setLocalization($localization);

        $parentNode = new ContentNode();
        $parentNode->addLocalizedUrl((new LocalizedFallbackValue())->setText($rootUrl));
        $parentNode->addContentVariant((new ContentVariant())->addSlug($parentSlug));

        $emptySlugPrototype = new LocalizedFallbackValue();
        $emptySlugPrototype->setLocalization($localization);
        $emptySlugPrototype->setString('');

        $routeData = new RouteData('test_route', []);
        $contentNode = $this->prepareContentNode($parentNode, $routeData, new Scope(), $emptySlugPrototype);

        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();

        $this->assertInstanceOf(ContentVariant::class, $actualContentVariant);
        $this->assertEmpty($actualContentVariant->getSlugs());
        $this->assertEmpty($contentNode->getLocalizedUrls());
    }

    public function testGenerateWithFallback()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        /** @var Localization $parentLocalization */
        $parentLocalization = $this->getEntity(Localization::class, ['id' => 123, 'name' => 'parent_localization']);
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test_localization']);
        $localization->setParentLocalization($parentLocalization);

        $parentNodeSlugUrl = '/parent/node';
        $parentSlug = new Slug();
        $parentSlug->setUrl($parentNodeSlugUrl);
        $parentSlug->setLocalization($parentLocalization);

        $parentContentVariant = new ContentVariant();
        $parentContentVariant->addSlug($parentSlug);

        $parentContentNode = new ContentNode();
        $parentContentNode->setWebCatalog($webCatalog);
        $parentContentNode->addContentVariant($parentContentVariant);
        $localizedUrl = (new LocalizedFallbackValue())->setText($parentNodeSlugUrl);
        $parentContentNode->addLocalizedUrl($localizedUrl);
        $parentContentNode->addContentVariant($parentContentVariant);

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizedValue')
            ->with($parentContentNode->getLocalizedUrls(), $localization)
            ->willReturn($localizedUrl);

        $this->doTestGenerate($localization, $parentContentNode);
    }

    public function testGetSlugsUrlForMovedNode()
    {
        $targetContentNode = new ContentNode();
        $sourceContentNode = new ContentNode();

        $this->uniqueSlugPrototypesResolver->expects($this->once())
            ->method('resolveSlugPrototypeUniqueness')
            ->with($targetContentNode, $sourceContentNode);

        $this->slugUrlDiffer->expects($this->once())
            ->method('getSlugUrlsChanges')
            ->with(new ArrayCollection(), new ArrayCollection())
            ->willReturn(['some_slugged/url']);

        $result = $this->slugGenerator->getSlugsUrlForMovedNode($targetContentNode, $sourceContentNode);
        $this->assertEquals(['some_slugged/url'], $result);
    }

    public function testGenerateWithExistingSlugs()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $contentVariantType = $this->createMock(ContentVariantTypeInterface::class);
        $contentVariantType->expects($this->once())
            ->method('getRouteData')
            ->willReturn($routData);

        $existingSlug = new Slug();
        $existingSlug->setUrl(SlugGenerator::ROOT_URL);

        $contentVariant = $this->createContentVariant($scope, 'test_type');
        $contentVariant->addSlug($existingSlug);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);
        $contentNode->addContentVariant($contentVariant);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->willReturn($contentVariantType);

        $this->redirectGenerator->expects($this->atLeast(1))
            ->method('updateRedirects');

        $this->redirectGenerator->expects($this->atLeast(1))
            ->method('generateForSlug');

        $this->slugGenerator->generate($contentNode, true);

        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();
        $actualSlugs = $actualContentVariant->getSlugs();

        $this->assertCount(1, $actualSlugs);
        $expectedSlug = (new Slug())
            ->setUrl(SlugGenerator::ROOT_URL)
            ->setRouteName($routeId)
            ->setRouteParameters($routeParameters)
            ->addScope($scope);

        $expectedUrl = (new LocalizedFallbackValue())->setText(SlugGenerator::ROOT_URL);
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertEquals($expectedUrl, $url);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertEquals($expectedSlug, $slug);
        }
    }

    public function testGenerateWithExistingSlugsWithoutGenerateRedirects()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $contentVariantType = $this->createMock(ContentVariantTypeInterface::class);
        $contentVariantType->expects($this->once())
            ->method('getRouteData')
            ->willReturn($routData);

        $existingSlug = new Slug();
        $existingSlug->setUrl(SlugGenerator::ROOT_URL);

        $contentVariant = $this->createContentVariant($scope, 'test_type');
        $contentVariant->addSlug($existingSlug);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);
        $contentNode->addContentVariant($contentVariant);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->willReturn($contentVariantType);

        $this->redirectGenerator->expects($this->once())
            ->method('updateRedirects');

        $this->redirectGenerator->expects($this->never())
            ->method('generateForSlug');

        $this->slugGenerator->generate($contentNode);

        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();
        $actualSlugs = $actualContentVariant->getSlugs();

        $this->assertCount(1, $actualSlugs);
        $expectedSlug = (new Slug())
            ->setUrl(SlugGenerator::ROOT_URL)
            ->setRouteName($routeId)
            ->setRouteParameters($routeParameters)
            ->addScope($scope);

        $expectedUrl = (new LocalizedFallbackValue())->setText(SlugGenerator::ROOT_URL);
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertEquals($expectedUrl, $url);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertEquals($expectedSlug, $slug);
        }
    }

    public function testGenerateWithoutLocalization()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $parentNodeSlugUrl = '/parent/node';
        $parentSlug = new Slug();
        $parentSlug->setUrl($parentNodeSlugUrl);

        $parentContentVariant = new ContentVariant();
        $parentContentVariant->addSlug($parentSlug);

        $parentContentNode = new ContentNode();
        $parentContentNode->setWebCatalog($webCatalog);
        $parentContentNode->addContentVariant($parentContentVariant);
        $localizedUrl = (new LocalizedFallbackValue())->setText($parentNodeSlugUrl);
        $parentContentNode->addLocalizedUrl($localizedUrl);
        $parentContentNode->addContentVariant($parentContentVariant);

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('test-url');

        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizedValue')
            ->with($parentContentNode->getLocalizedUrls(), null)
            ->willReturn($localizedUrl);

        $contentNode = $this->prepareContentNode($parentContentNode, $routData, $scope, $slugPrototype);
        $contentNode->setWebCatalog($webCatalog);
        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();
        $actualSlugs = $actualContentVariant->getSlugs();

        $this->assertCount(1, $actualSlugs);
        $expectedSlug = (new Slug())
            ->setUrl('/parent/node/test-url')
            ->setSlugPrototype('test-url')
            ->setRouteName($routeId)
            ->setRouteParameters($routeParameters)
            ->addScope($scope)
            ->setOrganization($organization);

        $expectedUrl = (new LocalizedFallbackValue())->setText('/parent/node/test-url');
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertEquals($expectedUrl, $url);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertEquals($expectedSlug, $slug);
            $this->assertNull($slug->getLocalization());
        }
    }

    public function testGenerateForVariantWithoutScopes()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('test-url');

        $routeData = new RouteData('route_id', []);
        $scope = new Scope();

        $contentVariantType = $this->createMock(ContentVariantTypeInterface::class);
        $contentVariantType->expects($this->once())
            ->method('getRouteData')
            ->willReturn($routeData);

        $slug = new Slug();
        $slug->setUrl('/test-url');

        $contentVariant = new ContentVariant();
        $contentVariant->setType('test_type');
        $contentVariant->addSlug($slug);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);
        $contentNode->setParentNode(new ContentNode());
        $contentNode->addContentVariant($contentVariant);
        $contentNode->addSlugPrototype($slugPrototype);
        $contentNode->addScope($scope);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->willReturn($contentVariantType);

        $this->slugGenerator->generate($contentNode);

        $this->assertCount(0, $contentVariant->getSlugs());
    }

    protected function doTestGenerate(Localization $localization, ContentNode $parentContentNode)
    {
        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setLocalization($localization);
        $slugPrototype->setString('test-url');

        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $contentNode = $this->prepareContentNode($parentContentNode, $routData, $scope, $slugPrototype);

        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();
        $actualSlugs = $actualContentVariant->getSlugs();

        $this->assertCount(1, $actualSlugs);
        $expectedSlug = (new Slug())
            ->setUrl('/parent/node/test-url')
            ->setSlugPrototype('test-url')
            ->setRouteName($routeId)
            ->setRouteParameters($routeParameters)
            ->addScope($scope)
            ->setLocalization($localization)
            ->setOrganization($parentContentNode->getWebCatalog()->getOrganization());

        $expectedUrl = (new LocalizedFallbackValue())->setText('/parent/node/test-url')->setLocalization($localization);
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertEquals($expectedUrl, $url);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertEquals($expectedSlug, $slug);
        }
    }

    /**
     * @param ContentNode $parentContentNode
     * @param RouteData $routData
     * @param Scope $scope
     * @param LocalizedFallbackValue $slugPrototype
     * @return ContentNode
     */
    protected function prepareContentNode(
        ContentNode $parentContentNode,
        RouteData $routData,
        Scope $scope,
        LocalizedFallbackValue $slugPrototype
    ) {
        $contentVariantType = $this->createMock(ContentVariantTypeInterface::class);
        $contentVariantType->expects($this->once())
            ->method('getRouteData')
            ->willReturn($routData);

        $contentVariant = $this->createContentVariant($scope, 'test_type');

        $organization = $this->getEntity(Organization::class, ['id' => 2]);
        $webCatalog = new WebCatalog();
        $webCatalog->setOrganization($organization);

        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);
        $contentNode->setParentNode($parentContentNode);
        $contentNode->addContentVariant($contentVariant);
        $contentNode->addSlugPrototype($slugPrototype);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->willReturn($contentVariantType);
        $this->uniqueSlugPrototypesResolver->expects($this->once())
            ->method('resolveSlugPrototypeUniqueness')
            ->with($parentContentNode, $contentNode);

        $this->slugGenerator->generate($contentNode);

        return $contentNode;
    }

    /**
     * @param Scope $scope
     * @param $type
     * @return ContentVariant
     */
    protected function createContentVariant(Scope $scope, $type)
    {
        $contentVariant = new ContentVariant();
        $contentVariant->setType($type);
        $contentVariant->addScope($scope);

        return $contentVariant;
    }

    public function testGetSlugs()
    {
        $englishLocalization = $this->getEntity(Localization::class, ['id' => 1]);
        $frenchLocalization = $this->getEntity(Localization::class, ['id' => 3]);

        /** @var LocalizedFallbackValue $defaultSlug */
        $defaultSlug = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'defaultUrl']);
        /** @var LocalizedFallbackValue $englishSlug */
        $englishSlug = $this->getEntity(LocalizedFallbackValue::class, [
            'string' => 'englishUrl',
            'localization' => $englishLocalization
        ]);
        /** @var LocalizedFallbackValue $frenchSlug */
        $frenchSlug = $this->getEntity(LocalizedFallbackValue::class, [
            'string' => 'frenchUrl',
            'localization' => $frenchLocalization
        ]);

        $this->localizationHelper
            ->expects($this->any())
            ->method('getLocalizations')
            ->willReturn([$englishLocalization, $frenchLocalization]);

        $urlDefault = $this->getEntity(LocalizedFallbackValue::class, ['text' => '/parent/default']);
        $urlEnglish = $this->getEntity(LocalizedFallbackValue::class, ['text' => '/parent/english']);
        $urlFrench = $this->getEntity(LocalizedFallbackValue::class, ['text' => '/parent/french']);

        $parentNode = $this->getEntity(ContentNode::class, ['localizedUrls' => [$urlDefault, $urlEnglish, $urlFrench]]);
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['parentNode' => $parentNode]);
        $contentNode
            ->addSlugPrototype($defaultSlug)
            ->addSlugPrototype($englishSlug)
            ->addSlugPrototype($frenchSlug);

        $localizedUrls = $parentNode->getLocalizedUrls();

        $this->localizationHelper
            ->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnMap([
                [$localizedUrls, null, $urlDefault],
                [$localizedUrls, $englishLocalization, $urlEnglish],
                [$localizedUrls, $frenchLocalization, $urlFrench],
            ]);

        $defaultLocalizationId = 0;
        $expectedSlugUrls = new ArrayCollection([
            $defaultLocalizationId => new SlugUrl('/parent/default/defaultUrl', null, 'defaultUrl'),
            $englishLocalization->getId()
            => new SlugUrl('/parent/english/englishUrl', $englishLocalization, 'englishUrl'),
            $frenchLocalization->getId() => new SlugUrl('/parent/french/frenchUrl', $frenchLocalization, 'frenchUrl')
        ]);

        $this->assertEquals($expectedSlugUrls, $this->slugGenerator->prepareSlugUrls($contentNode));
    }

    public function testGetSlugsWithEmptySlugs()
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class);

        $this->assertEquals(new ArrayCollection(), $this->slugGenerator->prepareSlugUrls($contentNode));
    }
}
