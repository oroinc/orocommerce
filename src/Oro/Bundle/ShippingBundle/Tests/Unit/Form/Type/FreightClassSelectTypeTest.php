<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Form\Type\FreightClassSelectType;
use Oro\Bundle\ShippingBundle\Provider\FreightClassesProvider;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FreightClassSelectTypeTest extends AbstractShippingOptionSelectTypeTest
{
    /** @var FreightClassSelectType */
    protected $formType;

    /** @var FreightClassesProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = $this->getMockBuilder(FreightClassesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configureFormatter();

        $this->formType = new FreightClassSelectType($this->provider, $this->formatter);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(FreightClassSelectType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $inputData, array $expectedData)
    {
        $formView = new FormView();
        $formView->vars['choices'] = [];

        $form = $this->createMock(FormInterface::class);
        $form->expects(static::once())
            ->method('getParent')
            ->willReturnCallback(function () use ($inputData) {
                if (null === $inputData['parentData']) {
                    return null;
                }

                $parent = $this->createMock(FormInterface::class);
                $parent->expects(static::once())
                    ->method('getData')
                    ->willReturn($inputData['parentData']);

                return $parent;
            });

        $this->provider->expects($inputData['freightClasses'] ? static::once() : static::never())
            ->method('getFreightClasses')
            ->with($inputData['parentData'], $inputData['options']['compact'])
            ->willReturn($inputData['freightClasses']);

        $this->formatter->expects(static::any())
            ->method('format')
            ->willReturnCallback(function ($item) {
                return $item . '.formatted';
            });

        $this->formType->finishView($formView, $form, $inputData['options']);

        static::assertEquals($expectedData, $formView->vars);
    }

    /**
     * @return array
     */
    public function finishViewProvider()
    {
        return [
            'full_list and no parent' => [
                'input' => [
                    'freightClasses' => null,
                    'parentData' => null,
                    'options' => [
                        'full_list' => true,
                        'compact' => false,
                    ],
                ],
                'expected' => [
                    'value' => null,
                    'attr' => [],
                    'choices' => [],
                ],
            ],
            '!full_list and parent' => [
                'input' => [
                    'freightClasses' => [
                        (new FreightClass())->setCode('code1'),
                        (new FreightClass())->setCode('code2'),
                    ],
                    'parentData' => new ProductShippingOptions(),
                    'options' => [
                        'full_list' => false,
                        'compact' => false,
                    ],
                ],
                'expected' => [
                    'value' => null,
                    'attr' => [],
                    'choices' => [
                        new ChoiceView((new FreightClass())->setCode('code1'), 'code1', 'code1.formatted'),
                        new ChoiceView((new FreightClass())->setCode('code2'), 'code2', 'code2.formatted'),
                    ],
                ],
            ],
        ];
    }
}
