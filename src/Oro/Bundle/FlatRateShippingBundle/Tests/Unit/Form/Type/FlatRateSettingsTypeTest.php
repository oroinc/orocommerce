<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateSettingsType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class FlatRateSettingsTypeTest extends FormIntegrationTestCase
{
    /** @var FlatRateSettingsType */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new FlatRateSettingsType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    new LocalizedPropertyType(),
                    new LocalizationCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionType($registry),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitData
     * @param FlatRateSettings $flatRateSettings
     */
    public function testSubmitValid($submitData, FlatRateSettings $flatRateSettings)
    {
        $form = $this->factory->create($this->formType, $flatRateSettings);

        $this->assertSame($flatRateSettings, $form->getData());

        $form->submit($submitData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($flatRateSettings, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'submitData' => [
                    'labels' => [
                        'values' => [ 'default' => 'first label'],
                    ],
                ],
                'FlatRateSettings' => (new FlatRateSettings())
                    ->addLabel((new LocalizedFallbackValue())->setString('Flat rate'))
            ],
        ];
    }

    public function testGetBlockPrefixReturnsString()
    {
        static::assertTrue(is_string($this->formType->getBlockPrefix()));
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'data_class' => FlatRateSettings::class
            ]);

        $this->formType->configureOptions($resolver);
    }
}
