<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FixedProductShippingBundle\Entity\FixedProductSettings;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductSettingsType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class FixedProductSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    private const LOCALIZATION_ID = 998;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $repositoryLocalization = $this->createMock(ObjectRepository::class);
        $repositoryLocalization->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return $this->getEntity(Localization::class, ['id' => $id]);
            });

        $repositoryLocalizedFallbackValue = $this->createMock(ObjectRepository::class);
        $repositoryLocalizedFallbackValue->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return $this->getEntity(LocalizedFallbackValue::class, ['id' => $id]);
            });

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Localization::class, null, $repositoryLocalization],
                [LocalizedFallbackValue::class, null, $repositoryLocalizedFallbackValue],
            ]);

        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    LocalizedPropertyType::class => new LocalizedPropertyType(),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionType($doctrine),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub([
                        $this->getEntity(Localization::class, ['id' => self::LOCALIZATION_ID]),
                    ]),
                    FallbackValueType::class => new FallbackValueType(),
                    FallbackPropertyType::class => new FallbackPropertyType($translator),
                ],
                [
                    FormType::class => [
                        new TooltipFormExtension($this->createMock(ConfigProvider::class), $translator),
                    ],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testSubmitValid(): void
    {
        $submitData = [
            'labels' => [
                'values' => [
                    'default' => 'Label 1',
                    'localizations' => [
                        self::LOCALIZATION_ID => [
                            'value' => 'Label 2',
                        ],
                    ],
                ],
            ],
        ];
        $form = $this->factory->create(FixedProductSettingsType::class);

        $form->submit($submitData);
        $expected = (new FixedProductSettings())
            ->addLabel($this->createLocalizedValue(
                'Label 2',
                null,
                $this->getEntity(Localization::class, ['id' => self::LOCALIZATION_ID])
            ))->addLabel($this->createLocalizedValue(
                'Label 1'
            ));
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    private function createLocalizedValue(
        ?string $string = null,
        ?string $text = null,
        ?Localization $localization = null
    ): LocalizedFallbackValue {
        $value = new LocalizedFallbackValue();
        $value->setString($string)
            ->setText($text)
            ->setLocalization($localization);

        return $value;
    }

    public function testGetBlockPrefixReturnsString(): void
    {
        $formType = new FixedProductSettingsType();
        $this->assertIsString($formType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => FixedProductSettings::class,
            ]);

        $formType = new FixedProductSettingsType();
        $formType->configureOptions($resolver);
    }
}
