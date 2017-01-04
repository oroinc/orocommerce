<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FlatRateBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateBundle\Form\Type\FlatRateSettingsType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

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
        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                ],
                []
            )
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
        $localization = new LocalizedFallbackValue();
        $localization->setString('Flat rate');
        return [
            [
                'submitData' => ['labels' => [['string' => 'Flat rate']]],
                'FlatRateSettings' => (new FlatRateSettings())
                    ->addLabel((new LocalizedFallbackValue())->setString('Flat rate'))
            ],
        ];
    }

    public function testGetBlockPrefixReturnsString()
    {
        static::assertTrue(is_string($this->formType->getBlockPrefix()));
    }
}
