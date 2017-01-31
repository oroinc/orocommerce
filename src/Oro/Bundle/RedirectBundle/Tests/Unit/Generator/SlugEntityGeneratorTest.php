<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\RedirectGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class SlugEntityGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RoutingInformationProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $routingInformationProvider;

    /**
     * @var RedirectGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirectGenerator;

    /**
     * @var SlugEntityGenerator
     */
    protected $generator;

    protected function setUp()
    {
        $this->routingInformationProvider = $this->createMock(RoutingInformationProviderInterface::class);
        $this->redirectGenerator = $this->getMockBuilder(RedirectGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->generator = new SlugEntityGenerator($this->routingInformationProvider, $this->redirectGenerator);
    }

    /**
     * @dataProvider generationDataProvider
     * @param SluggableInterface $entity
     * @param SluggableInterface $expected
     */
    public function testGenerate(SluggableInterface $entity, SluggableInterface $expected)
    {
        $this->routingInformationProvider->expects($this->any())
            ->method('getRouteData')
            ->willReturn(new RouteData('some_route', ['id' => 42]));

        $this->routingInformationProvider->expects($this->any())
            ->method('getUrlPrefix')
            ->willReturn('/test');

        $this->generator->generate($entity);
        $this->assertEquals($expected, $entity);
    }

    /**
     * @return array
     */
    public function generationDataProvider()
    {
        $localizationOne = $this->getEntity(Localization::class, ['id' => 1]);
        $localizationTwo = $this->getEntity(Localization::class, ['id' => 2]);
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
        $valueFive = new LocalizedFallbackValue();
        $valueFive->setString('');
        $valueFive->setLocalization($localizationTwo);

        $defaultSlug = (new Slug())
            ->setUrl('/test/test1')
            ->setRouteName('some_route')
            ->setRouteParameters(['id' => 42]);
        $slugTwo = (new Slug())
            ->setUrl('/test/test2')
            ->setLocalization($localizationOne)
            ->setRouteName('some_route')
            ->setRouteParameters(['id' => 42]);

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
                    ->addSlugPrototype($valueFive)
                    ->addSlug($defaultSlug),
                (new SluggableEntityStub())
                    ->addSlugPrototype($valueOne)
                    ->addSlugPrototype($valueTwo)
                    ->addSlugPrototype($valueFour)
                    ->addSlugPrototype($valueFive)
                    ->addSlug($defaultSlug)
                    ->addSlug($slugTwo)
                    ->addSlug(
                        (new Slug())
                            ->setUrl('/test/test4')
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
                            ->setLocalization($localizationOne)
                            ->setRouteName('some_route')
                            ->setRouteParameters(['id' => 42])
                    )
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

        $this->redirectGenerator->expects($this->once())
            ->method('generate');

        $this->generator->generate($entity, true);
        $this->assertEquals($expected, $entity);
    }
}
