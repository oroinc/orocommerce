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
     */
    public function testFinishView(array $options, $data)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects(static::once())
            ->method('getData')
            ->willReturn($data);

        $formView = new FormView();
        $this->type->finishView($formView, $form, $options);

        static::assertArrayHasKey('mode', $formView->vars);
        $options['mode'] = $formView->vars['mode'];

        static::assertArrayHasKey('value', $formView->vars);
        $options['data'] = $formView->vars['value'];
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
    {
        return [
            'test1' => [
                'options' => [
                    'mode' => 'select',
                ],
                'data' => 'data1'
            ],
            'test2' => [
                'options' => [
                    'mode' => 'input',
                ],
                'data' => 'data2'
            ],
        ];
    }
}
