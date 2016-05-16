<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Form\Type\FreightClassSelectType;
use OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProvider;

class FreightClassSelectTypeTest extends AbstractShippingOptionSelectTypeTest
{
    /** @var FreightClassSelectType */
    protected $formType;
    
    /** @var FreightClassesProvider */
    protected $provider;
    
    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\FreightClassesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new FreightClassSelectType($this->provider, $this->formatter);
    }

    public function testGetName()
    {
        $this->assertEquals(FreightClassSelectType::NAME, $this->formType->getName());
    }
    
    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $inputData, array $expectedData)
    {
        $formView = new FormView();
        $formView->vars['choices'] = [];
        
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getParent')
            ->willReturnCallback(function () use ($inputData) {
                if (null === $inputData['parentData']) {
                    return null;
                }

                $parent = $this->getMock('Symfony\Component\Form\FormInterface');
                $parent->expects($this->once())
                    ->method('getData')
                    ->willReturn($inputData['parentData']);
                
                return $parent;
            });
        
        $this->provider->expects($inputData['freightClasses'] ? $this->once() : $this->never())
            ->method('getFreightClasses')
            ->with($inputData['parentData'], $inputData['options']['compact'])
            ->willReturn($inputData['freightClasses']);
        
        $this->formatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(function ($item) {
                return $item . '.formatted';
            });
        
        $this->formType->finishView($formView, $form, $inputData['options']);
        
        $this->assertEquals($expectedData, $formView->vars);
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
