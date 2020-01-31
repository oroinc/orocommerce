<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\SluggableEntityFormStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SlugifyFormHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $slugifyFormHelper;

    /**
     * @var SlugGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $slugGenerator;

    /**
     * @var LocalizedSlugType
     */
    private $formType;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        $this->slugifyFormHelper = $this->createMock(SlugifyFormHelper::class);
        $this->slugGenerator = $this->createMock(SlugGenerator::class);
        $this->formType = new LocalizedSlugType($this->slugifyFormHelper, $this->slugGenerator);

        $this->slugGenerator
            ->expects($this->any())
            ->method('slugify')
            ->willReturnCallback(
                static function (string $string) {
                    return $string . '-slug';
                }
            );

        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    $this->formType,
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
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::POST_SUBMIT,
                function () {
                }
            );

        $this->formType->buildForm($builder, []);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitData
     * @param Collection $expectedSlugPrototypes
     */
    public function testSubmit(array $submitData, Collection $expectedSlugPrototypes): void
    {
        $form = $this->factory->create(SluggableEntityFormStub::class, new SluggableEntityStub());

        $form->submit($submitData);

        $this->assertTrue($form->isValid());

        /** @var SluggableEntityStub $entity */
        $entity = $form->getData();
        $this->assertEquals($expectedSlugPrototypes, $entity->getSlugPrototypes());
    }

    /**
     * @return array
     */
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
     * @dataProvider fillDefaultSlugsDataProvider
     *
     * @param Collection $localizedSources
     * @param Collection $localizedSlugs
     * @param Collection $expectedLocalizedSlugs
     */
    public function testFillDefaultSlugs(
        Collection $localizedSources,
        Collection $localizedSlugs,
        Collection $expectedLocalizedSlugs
    ): void {
        $this->formType->fillDefaultSlugs($localizedSources, $localizedSlugs);

        $this->assertEquals($expectedLocalizedSlugs, $localizedSlugs);
    }

    /**
     * @return array
     */
    public function fillDefaultSlugsDataProvider(): array
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
                'expectedLocalizedSlugs' => new ArrayCollection(),
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
        ];
    }

    /**
     * @param string $string
     * @param Localization|null $localization
     *
     * @return LocalizedFallbackValue
     */
    private function createLocalizedFallbackValue(
        string $string,
        ?Localization $localization = null
    ): LocalizedFallbackValue {
        return (new LocalizedFallbackValue())->setString($string)->setLocalization($localization);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
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
        $resolver->expects($this->once())->method('setDefined')->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $options = ['someOptionName' => 'someOptionValue'];

        $this->slugifyFormHelper->expects($this->once())
            ->method('addSlugifyOptionsLocalized')
            ->with($view, $options);

        $this->formType->buildView($view, $form, $options);
    }
}
