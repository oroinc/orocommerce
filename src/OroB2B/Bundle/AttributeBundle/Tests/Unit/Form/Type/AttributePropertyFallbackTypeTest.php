<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\AttributeBundle\Form\Type\AttributePropertyFallbackType;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;

class AttributePropertyFallbackTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AttributePropertyFallbackType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new AttributePropertyFallbackType();
    }

    /**
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param mixed $submittedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, $submittedData)
    {
        $form = $this->factory->create($this->formType, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'default options' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'required' => false,
                    'empty_value' => 'orob2b.attribute.form.fallback.none',
                    'choices' => [
                        FallbackType::SYSTEM => 'orob2b.attribute.form.fallback.default',
                    ],
                ],
                'submittedData' => FallbackType::SYSTEM,
            ],
            'parent locale' => [
                'inputOptions' => [
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALE]
                ],
                'expectedOptions' => [
                    'required' => false,
                    'empty_value' => 'orob2b.attribute.form.fallback.none',
                    'choices' => [
                        FallbackType::PARENT_LOCALE => 'orob2b.attribute.form.fallback.parent_locale',
                        FallbackType::SYSTEM => 'orob2b.attribute.form.fallback.default',
                    ],
                ],
                'submittedData' => FallbackType::PARENT_LOCALE,
            ],
            'custom choices' => [
                'inputOptions' => [
                    'choices' => [0 => '0', 1 => '1'],
                ],
                'expectedOptions' => [
                    'choices' => [0 => '0', 1 => '1'],
                ],
                'submittedData' => null,
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(AttributePropertyFallbackType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
