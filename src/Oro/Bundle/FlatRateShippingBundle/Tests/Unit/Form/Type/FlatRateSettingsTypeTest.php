<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateSettingsType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlatRateSettingsTypeTest extends FormIntegrationTestCase
{
    private const LOCALIZATION_ID = 998;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $repositoryLocalization = $this->createMock(ObjectRepository::class);
        $repositoryLocalization->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return $this->getLocalization($id);
            });

        $repositoryLocalizedFallbackValue = $this->createMock(ObjectRepository::class);
        $repositoryLocalizedFallbackValue->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                $value = new LocalizedFallbackValue();
                ReflectionUtil::setId($value, $id);

                return $value;
            });

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Localization::class, null, $repositoryLocalization],
                [LocalizedFallbackValue::class, null, $repositoryLocalizedFallbackValue],
            ]);

        return [
            new PreloadedExtension(
                [
                    new LocalizedPropertyType(),
                    new LocalizedFallbackValueCollectionType($doctrine),
                    new FallbackValueType(),
                    new FallbackPropertyType($this->createMock(TranslatorInterface::class)),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub([
                        $this->getLocalization(self::LOCALIZATION_ID)
                    ]),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    private function getLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    public function testSubmitValid()
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
        $form = $this->factory->create(FlatRateSettingsType::class);

        $form->submit($submitData);
        $expected = (new FlatRateSettings())
            ->addLabel($this->createLocalizedValue('Label 2', null, $this->getLocalization(self::LOCALIZATION_ID)))
            ->addLabel($this->createLocalizedValue('Label 1'));
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    private function createLocalizedValue(
        string $string,
        string $text = null,
        Localization $localization = null
    ): LocalizedFallbackValue {
        $value = new LocalizedFallbackValue();
        $value->setString($string)
            ->setText($text)
            ->setLocalization($localization);

        return $value;
    }

    public function testGetBlockPrefixReturnsString()
    {
        $formType = new FlatRateSettingsType();
        self::assertIsString($formType->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => FlatRateSettings::class]);

        $formType = new FlatRateSettingsType();
        $formType->configureOptions($resolver);
    }
}
