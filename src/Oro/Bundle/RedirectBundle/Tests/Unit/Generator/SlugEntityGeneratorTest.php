<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\RedirectGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Generator\UniqueSlugResolver;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityWithOrganizationStub;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class SlugEntityGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var RoutingInformationProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $routingInformationProvider;

    /**
     * @var UniqueSlugResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $slugResolver;

    /**
     * @var RedirectGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $redirectGenerator;

    /**
     * @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlStorageCache;

    /**
     * @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userLocalizationManager;

    /**
     * @var SlugEntityGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->routingInformationProvider = $this->createMock(RoutingInformationProviderInterface::class);
        $this->slugResolver = $this->createMock(UniqueSlugResolver::class);
        $this->redirectGenerator = $this->createMock(RedirectGenerator::class);
        $this->urlStorageCache = $this->createMock(UrlCacheInterface::class);
        $this->userLocalizationManager = $this->createMock(UserLocalizationManager::class);

        $this->generator = new SlugEntityGenerator(
            $this->routingInformationProvider,
            $this->slugResolver,
            $this->redirectGenerator,
            $this->urlStorageCache,
            $this->userLocalizationManager
        );
    }

    /**
     * @dataProvider generationDataProvider
     */
    public function testGenerate(SluggableInterface $entity, SluggableInterface $expected)
    {
        $localizations = [
            $this->getEntity(Localization::class, ['id' => 1001])
        ];

        $this->userLocalizationManager
            ->expects(self::any())
            ->method('getEnabledLocalizations')
            ->willReturn($localizations);

        $this->routingInformationProvider->expects($this->any())
            ->method('getRouteData')
            ->willReturn(new RouteData('some_route', ['id' => 42]));

        $this->routingInformationProvider->expects($this->any())
            ->method('getUrlPrefix')
            ->willReturn('/test');

        /** @var Slug[] $expectedSlugs */
        $expectedSlugs = array_values($expected->getSlugs()->toArray());
        foreach ($expectedSlugs as $key => $slug) {
            $this->slugResolver->expects($this->at($key))
                ->method('resolve')
                ->willReturn($slug->getUrl());
        }

        $this->urlStorageCache
            ->expects(self::once())
            ->method('removeUrl')
            ->with('some_route', ['id' => 42], 1001);

        $this->generator->generate($entity);
        $this->assertEquals($expected, $entity);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function generationDataProvider()
    {
        $localizationOne = $this->getEntity(Localization::class, ['id' => 1]);
        $localizationTwo = $this->getEntity(Localization::class, ['id' => 2]);

        $emptyStringValue = new LocalizedFallbackValue();
        $emptyStringValue->setString('');

        $valueOne = new LocalizedFallbackValue();
        $valueOne->setString('test1');

        $valueTwo = new LocalizedFallbackValue();
        $valueTwo->setString('test2');
        $valueTwo->setLocalization($localizationOne);

        $valueThree = new LocalizedFallbackValue();
        $valueThree->setString('test3');
        $valueThree->setLocalization($localizationOne);

        $valueFour = new LocalizedFallbackValue();
        $valueFour->setString('test4');
        $valueFour->setLocalization($localizationTwo);

        $defaultSlug = (new Slug())
            ->setUrl('/test/test1')
            ->setSlugPrototype('test1')
            ->setRouteName('some_route')
            ->setRouteParameters(['id' => 42]);
        $slugTwo = (new Slug())
            ->setUrl('/test/test2')
            ->setSlugPrototype('test2')
            ->setLocalization($localizationOne)
            ->setRouteName('some_route')
            ->setRouteParameters(['id' => 42]);

        $organization = new Organization();
        $organization->setName('Oro');
        $slugTwoWithOrganization = clone $slugTwo;
        $slugTwoWithOrganization->setOrganization($organization);

        return [
            'no slugs' => [
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueOne),
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueOne)
                    ->addSlug($defaultSlug)
            ],
            'one existing one added' => [
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueOne)
                    ->addSlugPrototype($valueTwo)
                    ->addSlug($defaultSlug),
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueOne)
                    ->addSlugPrototype($valueTwo)
                    ->addSlug($defaultSlug)
                    ->addSlug($slugTwo)
            ],
            'existing removed one added' => [
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueTwo)
                    ->addSlug($defaultSlug),
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueTwo)
                    ->addSlug($defaultSlug)
                    ->addSlug($slugTwo)
                    ->removeSlug($defaultSlug)
            ],
            'added for different locale' => [
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueOne)
                    ->addSlugPrototype($valueTwo)
                    ->addSlugPrototype($valueFour)
                    ->addSlug($defaultSlug),
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueOne)
                    ->addSlugPrototype($valueTwo)
                    ->addSlugPrototype($valueFour)
                    ->addSlug($defaultSlug)
                    ->addSlug($slugTwo)
                    ->addSlug(
                        (new Slug())
                            ->setUrl('/test/test4')
                            ->setSlugPrototype('test4')
                            ->setLocalization($localizationTwo)
                            ->setRouteName('some_route')
                            ->setRouteParameters(['id' => 42])
                    )
            ],
            'updated by locale' => [
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueTwo)
                    ->addSlugPrototype($valueThree)
                    ->addSlug($slugTwo),
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueTwo)
                    ->addSlugPrototype($valueThree)
                    ->addSlug(
                        (new Slug())
                            ->setUrl('/test/test3')
                            ->setSlugPrototype('test3')
                            ->setLocalization($localizationOne)
                            ->setRouteName('some_route')
                            ->setRouteParameters(['id' => 42])
                    )
            ],
            'added empty' => [
                (new SluggableEntityStub())
                    ->addSlugPrototype($emptyStringValue),
                (new SluggableEntityStub())
                    ->addSlugPrototype($emptyStringValue)
            ],
            'existing changed to empty' => [
                (new SluggableEntityStub())
                    ->addSlugPrototype($emptyStringValue)
                    ->addSlug($defaultSlug),
                (new SluggableEntityStub())
                    ->addSlugPrototype($emptyStringValue)
            ],
            'added with organization' => [
                (new SluggableEntityWithOrganizationStub())
                    ->setOrganization($organization)
                    ->addSlugPrototype($valueOne)
                    ->addSlugPrototype($valueTwo)
                    ->addSlug($defaultSlug),
                (new SluggableEntityWithOrganizationStub())
                    ->setOrganization($organization)
                    ->addSlugPrototype($valueOne)
                    ->addSlugPrototype($valueTwo)
                    ->addSlug($defaultSlug)
                    ->addSlug($slugTwoWithOrganization)
            ],
        ];
    }

    public function testGenerateWithRedirects()
    {
        $localization = $this->getEntity(Localization::class, ['id' => 1]);

        $valueOne = new LocalizedFallbackValue();
        $valueOne->setString('test1');
        $valueTwo = new LocalizedFallbackValue();
        $valueTwo->setString('test2');
        $valueTwo->setLocalization($localization);

        $defaultSlug = (new Slug())
            ->setUrl('/test/test1')
            ->setRouteName('some_route')
            ->setRouteParameters(['id' => 42]);
        $slugTwo = (new Slug())
            ->setUrl('/test/test2')
            ->setSlugPrototype('test2')
            ->setLocalization($localization)
            ->setRouteName('some_route')
            ->setRouteParameters(['id' => 42]);

        $entity = new SluggableEntityStub();
        $entity->addSlugPrototype($valueOne)
            ->addSlugPrototype($valueTwo)
            ->addSlug($defaultSlug);

        $expected = new SluggableEntityStub();
        $expected->addSlugPrototype($valueOne)
            ->addSlugPrototype($valueTwo)
            ->addSlug($defaultSlug)
            ->addSlug($slugTwo);

        $this->routingInformationProvider->expects($this->any())
            ->method('getRouteData')
            ->willReturn(new RouteData('some_route', ['id' => 42]));

        $this->routingInformationProvider->expects($this->any())
            ->method('getUrlPrefix')
            ->willReturn('/test');

        $this->slugResolver->expects($this->at(0))
            ->method('resolve')
            ->willReturn('/test/test1');

        $this->slugResolver->expects($this->at(1))
            ->method('resolve')
            ->willReturn('/test/test2');

        $this->redirectGenerator->expects($this->once())
            ->method('updateRedirects');

        $this->redirectGenerator->expects($this->once())
            ->method('generateForSlug');

        $this->userLocalizationManager
            ->expects(self::any())
            ->method('getEnabledLocalizations')
            ->willReturn([]);

        $this->generator->generate($entity, true);
        $this->assertEquals($expected, $entity);
    }

    public function testGenerateWhenSlugPrototypesUpdated()
    {
        /** @var LocalizedFallbackValue $slugPrototype */
        $slugPrototype = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'something']);
        $entity = (new SluggableEntityStub())->addSlugPrototype($slugPrototype);

        $this->slugResolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn('/some-prefix/something-1');

        $routeData = new RouteData('someRoute');
        $this->routingInformationProvider
            ->expects($this->at(3))
            ->method('getRouteData')
            ->with($entity)
            ->willReturn($routeData);

        $expectedSlugPrototypes = new ArrayCollection([
            $this->getEntity(LocalizedFallbackValue::class, ['string' => 'some-prefix/something-1'])
        ]);

        $this->userLocalizationManager
            ->expects(self::any())
            ->method('getEnabledLocalizations')
            ->willReturn([]);

        $this->generator->generate($entity);
        $this->assertEquals($expectedSlugPrototypes, $entity->getSlugPrototypes());
    }

    public function testPrepareSlugUrls()
    {
        /** @var Localization $englishLocalization */
        $englishLocalization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Localization $frenchLocalization */
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

        $sluggableEntity = (new SluggableEntityStub())
            ->addSlugPrototype($defaultSlug)
            ->addSlugPrototype($englishSlug)
            ->addSlugPrototype($frenchSlug);

        $this->routingInformationProvider
            ->expects($this->any())
            ->method('getUrlPrefix')
            ->with($sluggableEntity)
            ->willReturn('prefix');

        $noLocalizationId = 0;
        $expectedSlugUrls = new ArrayCollection([
            $noLocalizationId => new SlugUrl('/prefix/defaultUrl', null, 'defaultUrl'),
            $englishLocalization->getId() => new SlugUrl('/prefix/englishUrl', $englishLocalization, 'englishUrl'),
            $frenchLocalization->getId() => new SlugUrl('/prefix/frenchUrl', $frenchLocalization, 'frenchUrl')
        ]);

        $this->assertEquals($expectedSlugUrls, $this->generator->prepareSlugUrls($sluggableEntity));
    }

    public function testPrepareSlugUrlsWithEmptySlugs()
    {
        $sluggableEntity = new SluggableEntityStub();

        $this->assertEquals(new ArrayCollection(), $this->generator->prepareSlugUrls($sluggableEntity));
    }

    public function testGetSlugsByEntitySlugPrototypes()
    {
        /** @var LocalizedFallbackValue $slugPrototype */
        $slugPrototype = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'something']);
        $entity = (new SluggableEntityStub())->addSlugPrototype($slugPrototype);

        $routeData = new RouteData('someRoute');
        $this->routingInformationProvider->expects($this->any())
            ->method('getRouteData')
            ->with($entity)
            ->willReturn($routeData);

        $this->routingInformationProvider->expects($this->any())
            ->method('getUrlPrefix')
            ->with($entity)
            ->willReturn('prefix');

        $slugs = $this->generator->getSlugsByEntitySlugPrototypes($entity);

        $this->assertInstanceOf(ArrayCollection::class, $slugs);
        $this->assertCount(1, $slugs);
        /** @var Slug $slug */
        $slug = $slugs->first();
        $this->assertEquals('/prefix/something', $slug->getUrl());
    }
}
