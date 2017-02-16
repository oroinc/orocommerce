<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Generator;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;

class SlugGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ContentVariantTypeRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentVariantTypeRegistry;

    /**
     * @var SlugGenerator
     */
    protected $slugGenerator;

    protected function setUp()
    {
        $this->contentVariantTypeRegistry = $this->createMock(ContentVariantTypeRegistry::class);
        $this->slugGenerator = new SlugGenerator($this->contentVariantTypeRegistry);
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

        $contentVariant = new ContentVariant();
        $contentVariant->setType('test_type');
        $contentVariant->addScope($scope);

        $contentNode = new ContentNode();
        $contentNode->addContentVariant($contentVariant);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->willReturn($contentVariantType);

        $this->slugGenerator->generate($contentNode);

        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();
        $actualSlugs = $actualContentVariant->getSlugs();

        $this->assertCount(1, $actualSlugs);
        $expectedSlugs = [
            (new Slug())->setUrl(SlugGenerator::ROOT_URL)
                ->setRouteName($routeId)
                ->setRouteParameters($routeParameters)
                ->addScope($scope)
        ];

        $this->assertCount(1, $contentNode->getLocalizedUrls());
        $expectedUrls = [(new LocalizedFallbackValue())->setText(SlugGenerator::ROOT_URL)];
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertContains($url, $expectedUrls, '', false, false);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertContains($slug, $expectedSlugs, '', false, false);
        }
    }

    public function testGenerate()
    {
        $localization = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test_localization']);

        $parentNodeSlugUrl = '/parent/node';
        $parentSlug = new Slug();
        $parentSlug->setUrl($parentNodeSlugUrl);
        $parentSlug->setLocalization($localization);

        $parentContentVariant = new ContentVariant();
        $parentContentVariant->addSlug($parentSlug);

        $parentContentNode = new ContentNode();
        $parentContentNode->addLocalizedUrl((new LocalizedFallbackValue())->setText($parentNodeSlugUrl));
        $parentContentNode->addContentVariant($parentContentVariant);

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
        $parentLocalization = $this->getEntity(Localization::class, ['id' => 123, 'name' => 'parent_localization']);
        $localization = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test_localization']);
        $localization->setParentLocalization($parentLocalization);

        $parentNodeSlugUrl = '/parent/node';
        $parentSlug = new Slug();
        $parentSlug->setUrl($parentNodeSlugUrl);
        $parentSlug->setLocalization($parentLocalization);

        $parentContentVariant = new ContentVariant();
        $parentContentVariant->addSlug($parentSlug);

        $parentContentNode = new ContentNode();
        $parentContentNode->addContentVariant($parentContentVariant);
        $parentContentNode->addLocalizedUrl((new LocalizedFallbackValue())->setText($parentNodeSlugUrl));
        $parentContentNode->addContentVariant($parentContentVariant);

        $this->doTestGenerate($localization, $parentContentNode);
    }

    public function testGenerateWithExistingSlugs()
    {
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

        $contentVariant = new ContentVariant();
        $contentVariant->setType('test_type1');
        $contentVariant->addScope($scope);
        $contentVariant->addSlug($existingSlug);

        $contentNode = new ContentNode();
        $contentNode->addContentVariant($contentVariant);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->willReturn($contentVariantType);

        $this->slugGenerator->generate($contentNode);

        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();
        $actualSlugs = $actualContentVariant->getSlugs();

        $this->assertCount(1, $actualSlugs);
        $expectedSlugs = [
            (new Slug())->setUrl(SlugGenerator::ROOT_URL)
                ->setRouteName($routeId)
                ->setRouteParameters($routeParameters)
                ->addScope($scope)
        ];

        $expectedUrls = [(new LocalizedFallbackValue())->setText(SlugGenerator::ROOT_URL)];
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertContains($url, $expectedUrls, '', false, false);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertContains($slug, $expectedSlugs, '', false, false);
        }
    }

    public function testGenerateWithoutLocalization()
    {
        $parentNodeSlugUrl = '/parent/node';
        $parentSlug = new Slug();
        $parentSlug->setUrl($parentNodeSlugUrl);

        $parentContentVariant = new ContentVariant();
        $parentContentVariant->addSlug($parentSlug);

        $parentContentNode = new ContentNode();
        $parentContentNode->addContentVariant($parentContentVariant);
        $parentContentNode->addLocalizedUrl((new LocalizedFallbackValue())->setText($parentNodeSlugUrl));
        $parentContentNode->addContentVariant($parentContentVariant);

        $slugPrototype = new LocalizedFallbackValue();
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
        $expectedSlugs = [
            (new Slug())->setUrl('/parent/node/test-url')
                ->setRouteName($routeId)
                ->setRouteParameters($routeParameters)
                ->addScope($scope)
        ];

        $expectedUrls = [(new LocalizedFallbackValue())->setText('/parent/node/test-url')];
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertContains($url, $expectedUrls, '', false, false);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertContains($slug, $expectedSlugs, '', false, false);
            $this->assertNull($slug->getLocalization());
        }
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
        $expectedSlugs = [
            (new Slug())->setUrl('/parent/node/test-url')
                ->setRouteName($routeId)
                ->setRouteParameters($routeParameters)
                ->addScope($scope)
                ->setLocalization($localization)
        ];

        $expectedUrls = [
            (new LocalizedFallbackValue())->setText('/parent/node/test-url')->setLocalization($localization)
        ];
        foreach ($contentNode->getLocalizedUrls() as $url) {
            $this->assertContains($url, $expectedUrls, '', false, false);
        }

        foreach ($actualSlugs as $slug) {
            $this->assertContains($slug, $expectedSlugs, '', false, false);
        }
    }

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

        $contentVariant = new ContentVariant();
        $contentVariant->setType('test_type');
        $contentVariant->addScope($scope);

        $contentNode = new ContentNode();
        $contentNode->setParentNode($parentContentNode);
        $contentNode->addContentVariant($contentVariant);
        $contentNode->addSlugPrototype($slugPrototype);

        $this->contentVariantTypeRegistry->expects($this->once())
            ->method('getContentVariantType')
            ->willReturn($contentVariantType);

        $this->slugGenerator->generate($contentNode);

        return $contentNode;
    }
}
