<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\AttributeBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\AttributePropertyFallbackType;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type\Stub\TextTypeStub;

class FallbackValueTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FallbackValueTypeTest
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FallbackValueType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    AttributePropertyFallbackType::NAME => new AttributePropertyFallbackType(),
                    TextTypeStub::NAME => new TextTypeStub(),
                ],
                []
            )
        ];
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $defaultData, $viewData, $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertNull($formConfig->getOption('data_class'));
        $this->assertEquals(AttributePropertyFallbackType::NAME, $formConfig->getOption('fallback_type'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider()
    {
        return [
            'percent with value' => [
                'options' => [
                    'type'    => 'percent',
                    'options' => ['type' => 'integer'],
                ],
                'defaultData'   => 25,
                'viewData'      => ['value' => 25, 'fallback' => null],
                'submittedData' => ['value' => '55', 'fallback' => ''],
                'expectedData'  => 55
            ],
            'text with fallback' => [
                'options' => [
                    'type'              => TextTypeStub::NAME,
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALE]
                ],
                'defaultData'   => new FallbackType(FallbackType::SYSTEM),
                'viewData'      => ['value' => null, 'fallback' => FallbackType::SYSTEM],
                'submittedData' => ['value' => '', 'fallback' => FallbackType::PARENT_LOCALE],
                'expectedData'  => new FallbackType(FallbackType::PARENT_LOCALE),
            ],
            'integer as null' => [
                'options' => [
                    'type' => 'integer',
                ],
                'defaultData'   => null,
                'viewData'      => ['value' => null, 'fallback' => null],
                'submittedData' => null,
                'expectedData'  => null
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(FallbackValueType::NAME, $this->formType->getName());
    }
}
