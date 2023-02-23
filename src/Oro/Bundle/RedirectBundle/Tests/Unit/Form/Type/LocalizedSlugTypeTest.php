<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Helper\SlugifyEntityHelper;
use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\SluggableEntityFormStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugTypeTest extends FormIntegrationTestCase
{
    /** @var SlugifyFormHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $slugifyFormHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LocalizedSlugType */
    private $formType;

    protected function setUp(): void
    {
        $this->slugifyFormHelper = $this->createMock(SlugifyFormHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $slugGenerator = $this->createMock(SlugGenerator::class);
        $slugGenerator->expects($this->any())
            ->method('slugify')
            ->willReturnCallback(static function (string $string) {
                return $string . '-slug';
            });

        $this->formType = new LocalizedSlugType(
            $this->slugifyFormHelper,
            new SlugifyEntityHelper($slugGenerator, $this->configManager, $this->doctrine)
        );

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                ],
                [FormType::class => []]
            ),
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, $this->isType('callable'));

        $this->formType->buildForm($builder, []);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitData, Collection $expectedSlugPrototypes): void
    {
        $form = $this->factory->create(
            SluggableEntityFormStub::class,
            new SluggableEntityStub(),
            ['source_field' => 'titles']
        );
        $form->submit($submitData);

        $this->assertTrue($form->isValid());

        /** @var SluggableEntityStub $entity */
        $entity = $form->getData();
        $this->assertEquals($expectedSlugPrototypes, $entity->getSlugPrototypes());
    }

    public function submitDataProvider(): array
    {
        return [
            'empty form' => [
                'submitData' => [],
                'expectedSlugPrototypes' => new ArrayCollection(),
            ],
            'title is set for only default localization' => [
                'submitData' => [
                    'titles' => [
                        ['string' => 'test'],
                    ],
                ],
                'expectedSlugPrototypes' => new ArrayCollection(
                    [(new LocalizedFallbackValue())->setString('test-slug')]
                ),
            ],
            'slug prototype is already set' => [
                'submitData' => [
                    'titles' => [['string' => 'test']],
                    'slugPrototypes' => [['string' => 'custom-slug']],
                ],
                'expectedSlugPrototypes' => new ArrayCollection(
                    [
                        (new LocalizedFallbackValue())->setString('custom-slug'),
                    ]
                ),
            ],
        ];
    }

    public function testSubmitWhenNoSourceField(): void
    {
        $form = $this->factory->create(
            SluggableEntityFormStub::class,
            new SluggableEntityStub(),
            ['source_field' => 'invalid_field']
        );

        $form->submit(['titles' => [['string' => 'test']]]);

        $this->assertTrue($form->isValid());

        /** @var SluggableEntityStub $entity */
        $entity = $form->getData();
        $this->assertEquals(new ArrayCollection(), $entity->getSlugPrototypes());
    }

    /**
     * @dataProvider onPostSubmitDataProvider
     */
    public function testOnPostSubmit(
        Collection $localizedSources,
        Collection $localizedSlugs,
        Collection $expectedLocalizedSlugs
    ): void {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('source_field')
            ->willReturn('source_field_name');

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getRoot')
            ->willReturn($form);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $sourceField = $this->createMock(FormConfigInterface::class);
        $sourceField->expects($this->once())
            ->method('getData')
            ->willReturn($localizedSources);

        $form->expects($this->once())
            ->method('has')
            ->with('source_field_name')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with('source_field_name')
            ->willReturn($sourceField);
        $form->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn($localizedSlugs);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->exactly(3))
            ->method('getForm')
            ->willReturn($form);

        $this->formType->onPostSubmit($event);

        $this->assertEquals($expectedLocalizedSlugs, $localizedSlugs);
    }

    public function onPostSubmitDataProvider(): array
    {
        $localization = $this->createMock(Localization::class);

        return [
            [
                'localizedSources' => new ArrayCollection(),
                'localizedSlugs' => new ArrayCollection(),
                'expectedLocalizedSlugs' => new ArrayCollection(),
            ],
            [
                'localizedSources' => new ArrayCollection([(new LocalizedFallbackValue())]),
                'localizedSlugs' => new ArrayCollection(),
                'expectedLocalizedSlugs' => new ArrayCollection([(new LocalizedFallbackValue())]),
            ],
            [
                'localizedSources' => new ArrayCollection([$this->createLocalizedFallbackValue('test')]),
                'localizedSlugs' => new ArrayCollection(),
                'expectedLocalizedSlugs' => new ArrayCollection(
                    [$this->createLocalizedFallbackValue('test-slug')]
                ),
            ],
            [
                'localizedSources' => new ArrayCollection([$this->createLocalizedFallbackValue('test')]),
                'localizedSlugs' => new ArrayCollection([$this->createLocalizedFallbackValue('custom-slug')]),
                'expectedLocalizedSlugs' => new ArrayCollection(
                    [$this->createLocalizedFallbackValue('custom-slug')]
                ),
            ],
            [
                'localizedSources' => new ArrayCollection(
                    [
                        $this->createLocalizedFallbackValue('test'),
                        $this->createLocalizedFallbackValue('test-en', $localization),
                    ]
                ),
                'localizedSlugs' => new ArrayCollection([$this->createLocalizedFallbackValue('custom-slug')]),
                'expectedLocalizedSlugs' => new ArrayCollection(
                    [
                        $this->createLocalizedFallbackValue('custom-slug'),
                        $this->createLocalizedFallbackValue('test-en-slug', $localization),
                    ]
                ),
            ],
            [
                'localizedSources' => new ArrayCollection([$this->createLocalizedFallbackValue('test')]),
                'localizedSlugs' => new ArrayCollection([$this->createLocalizedFallbackValue('', null, 1)]),
                'expectedLocalizedSlugs' => new ArrayCollection(
                    [$this->createLocalizedFallbackValue('test-slug', null, 1)]
                ),
            ],
        ];
    }

    private function createLocalizedFallbackValue(
        string $string,
        ?Localization $localization = null,
        ?int $id = null
    ): LocalizedFallbackValue {
        $localizedFallbackValue = new LocalizedFallbackValue();
        ReflectionUtil::setId($localizedFallbackValue, $id);
        $localizedFallbackValue->setString($string);
        $localizedFallbackValue->setLocalization($localization);

        return $localizedFallbackValue;
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals('oro_api_slugify_slug', $options['slugify_route']);
                    $this->assertTrue($options['slug_suggestion_enabled']);
                    self::assertTrue($options['exclude_parent_localization']);

                    return true;
                }
            )
        );
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $options = ['someOptionName' => 'someOptionValue'];

        $this->slugifyFormHelper->expects($this->once())
            ->method('addSlugifyOptionsLocalized')
            ->with($view, $options);

        $this->formType->buildView($view, $form, $options);
    }
}
