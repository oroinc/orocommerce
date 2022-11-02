<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\SelectSwitchInputType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SelectSwitchInputTypeTest extends FormIntegrationTestCase
{
    /** @var SelectSwitchInputType */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->type = new SelectSwitchInputType();
    }

    public function testGetParent()
    {
        self::assertSame(ChoiceType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals(SelectSwitchInputType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testFinishView(array $options, string $data, array $expected)
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($data);

        $formView = new FormView();
        $this->type->finishView($formView, $form, $options);

        self::assertArrayHasKey('page_component', $formView->vars);
        self::assertEquals($options['page_component'], $formView->vars['page_component']);

        self::assertArrayHasKey('page_component_options', $formView->vars);
        self::assertEquals($expected, $formView->vars['page_component_options']);
    }

    public function finishViewDataProvider(): array
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
