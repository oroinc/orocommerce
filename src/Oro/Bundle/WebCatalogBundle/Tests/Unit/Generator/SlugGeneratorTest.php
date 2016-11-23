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
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\ContentVariantTypeInterface;
use Oro\Component\WebCatalog\RouteData;

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
        $this->contentVariantTypeRegistry = $this->getMock(ContentVariantTypeRegistry::class);
        $this->slugGenerator = new SlugGenerator($this->contentVariantTypeRegistry);
    }

    public function testGenerateForRoot()
    {
        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $contentVariantType = $this->getMock(ContentVariantTypeInterface::class);
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
        $parentContentNode->addContentVariant($parentContentVariant);

        $slugUrl = 'test-url';
        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setLocalization($localization);
        $slugPrototype->setString($slugUrl);

        $contentVariantType = $this->getMock(ContentVariantTypeInterface::class);
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

        foreach ($actualSlugs as $slug) {
            $this->assertContains($slug, $expectedSlugs, '', false, false);
        }
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

        $slugUrl = 'test-url';
        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setLocalization($localization);
        $slugPrototype->setString($slugUrl);

        $contentVariantType = $this->getMock(ContentVariantTypeInterface::class);
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

        foreach ($actualSlugs as $slug) {
            $this->assertContains($slug, $expectedSlugs, '', false, false);
        }
    }

    public function testGenerateWithExistingSlugs()
    {
        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $contentVariantType = $this->getMock(ContentVariantTypeInterface::class);
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

        foreach ($actualSlugs as $slug) {
            $this->assertContains($slug, $expectedSlugs, '', false, false);
        }
    }

    public function testGenerateWithoutFallback()
    {
        $parentLocalization = $this->getEntity(Localization::class, ['id' => 123, 'name' => 'parent_localization']);
        $localization = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test_localization']);

        $parentNodeSlugUrl = '/parent/node';
        $parentSlug = new Slug();
        $parentSlug->setUrl($parentNodeSlugUrl);
        $parentSlug->setLocalization($parentLocalization);

        $parentContentVariant = new ContentVariant();
        $parentContentVariant->addSlug($parentSlug);

        $parentContentNode = new ContentNode();
        $parentContentNode->addContentVariant($parentContentVariant);

        $slugUrl = 'test-url';
        $scope = new Scope();

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setLocalization($localization);
        $slugPrototype->setString($slugUrl);

        $contentVariant = new ContentVariant();
        $contentVariant->setType('test_type');
        $contentVariant->addScope($scope);

        $contentNode = new ContentNode();
        $contentNode->setParentNode($parentContentNode);
        $contentNode->addContentVariant($contentVariant);
        $contentNode->addSlugPrototype($slugPrototype);

        $this->slugGenerator->generate($contentNode);

        /** @var ContentVariant $actualContentVariant */
        $actualContentVariant = $contentNode->getContentVariants()->first();
        $actualSlugs = $actualContentVariant->getSlugs();

        $this->assertCount(0, $actualSlugs);
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

        $slugUrl = 'test-url';
        $routeId = 'route_id';
        $routeParameters = [];
        $routData = new RouteData($routeId, $routeParameters);
        $scope = new Scope();

        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString($slugUrl);

        $contentVariantType = $this->getMock(ContentVariantTypeInterface::class);
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

        foreach ($actualSlugs as $slug) {
            $this->assertContains($slug, $expectedSlugs, '', false, false);
            $this->assertNull($slug->getLocalization());
        }
    }
}
