<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\TextTypeStub;
use OroB2B\Bundle\AttributeBundle\Form\Type\HiddenFallbackValueType;

class HiddenFallbackValueTypeTest extends FormIntegrationTestCase
{
    const LOCALE_ID = 1;

    /**
     * @var HiddenFallbackValueType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new HiddenFallbackValueType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        return [
            new PreloadedExtension(
                [
                    FallbackPropertyType::NAME => new FallbackPropertyType($translator),
                    FallbackValueType::NAME => new FallbackValueType(),
                    TextTypeStub::NAME => new TextTypeStub()
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
        $this->assertEquals(FallbackPropertyType::NAME, $formConfig->getOption('fallback_type'));
        $fallbackType = new FallbackType(FallbackType::SYSTEM);

        /** @var \Closure $callback */
        $callback = $formConfig->getOption('default_callback');
        $this->assertEquals(
            [HiddenFallbackValueType::FALLBACK_VALUE => $fallbackType],
            $callback($fallbackType)
        );

        $this->assertEquals('hidden', $formConfig->getOption('extend_value_type'));
        $this->assertSame([], $formConfig->getOption('options'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $this->assertEquals('hidden', $form->createView()->vars['extend_value_type']);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'option without default' => [
                'options' => [
                    'type'              => TextTypeStub::NAME,
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALE]
                ],
                'defaultData'   => [],
                'viewData'      => [],
                'submittedData' => [
                    'extend_value' => 1,
                    'fallback_value' => [
                        'value' => 'some_test',
                        'fallback' => ''
                    ]
                ],
                'expectedData'  => [
                    'extend_value' => 1,
                    'fallback_value' => 'some_test'
                ],
            ],
            'option with fallback' => [
                'options' => [
                    'type'              => TextTypeStub::NAME,
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALE]
                ],
                'defaultData'   => [
                    'extend_value' => 1,
                    'fallback_value' => 'test'
                ],
                'viewData'      => [
                    'extend_value' => 1,
                    'fallback_value' => 'test'
                ],
                'submittedData' => [
                    'extend_value' => null,
                    'fallback_value' => [
                        'value' => '',
                        'fallback' => FallbackType::PARENT_LOCALE
                    ]
                ],
                'expectedData'  => [
                    'extend_value' => null,
                    'fallback_value' => new FallbackType(FallbackType::PARENT_LOCALE)
                ],
            ],

        ];
    }

    public function testGetName()
    {
        $this->assertEquals(HiddenFallbackValueType::NAME, $this->formType->getName());
    }
}
