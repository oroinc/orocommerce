<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;

class FallbackPropertyTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FallbackPropertyType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FallbackPropertyType();
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
        $this->assertTrue($form->isValid());
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
                    'empty_value' => false,
                    'choices' => [
                        FallbackType::SYSTEM => 'orob2b.fallback.type.default',
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
                    'empty_value' => false,
                    'choices' => [
                        FallbackType::PARENT_LOCALE => 'orob2b.fallback.type.parent_locale',
                        FallbackType::SYSTEM => 'orob2b.fallback.type.default',
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
        $this->assertEquals(FallbackPropertyType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
