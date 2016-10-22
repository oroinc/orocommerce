<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\SelectSwitchInputType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SelectSwitchInputTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SelectSwitchInputType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->type = new SelectSwitchInputType();
    }

    public function testGetName()
    {
        static::assertEquals(SelectSwitchInputType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        static::assertSame(ChoiceType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(SelectSwitchInputType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider finishViewDataProvider
     * @param array $options
     * @param string $data
     * @param array $expected
     */
    public function testFinishView(array $options, $data, $expected)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects(static::once())
            ->method('getData')
            ->willReturn($data);

        $formView = new FormView();
        $this->type->finishView($formView, $form, $options);

        static::assertArrayHasKey('page_component', $formView->vars);
        static::assertEquals($options['page_component'], $formView->vars['page_component']);

        static::assertArrayHasKey('page_component_options', $formView->vars);
        static::assertEquals($expected, $formView->vars['page_component_options']);
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
    {
        return [
            'test1' => [
                'options' => [
                    'page_component' => 'page_component1',
                    'page_component_options' => [],
                    'mode' => 'select',
                    'choices' => [1]
                ],
                'data' => 'data1',
                'expected' => [
                    'mode' => 'select',
                    'choices' => [1],
                    'value' => 'data1'
                ]
            ],
            'test2' => [
                'options' => [
                    'page_component' => 'page_component2',
                    'page_component_options' => [],
                    'mode' => 'input',
                    'choices' => [2]
                ],
                'data' => 'data2',
                'expected' => [
                    'mode' => 'input',
                    'choices' => [2],
                    'value' => 'data2'
                ]
            ],
        ];
    }
}
