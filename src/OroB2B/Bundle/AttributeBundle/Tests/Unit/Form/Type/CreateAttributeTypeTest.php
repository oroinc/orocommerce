<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AttributeBundle\Form\Type\AttributeTypeType;
use OroB2B\Bundle\AttributeBundle\Form\Type\CreateAttributeType;

class CreateAttributeTypeTest extends \PHPUnit_Framework_TestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\AttributeBundle\Entity\Attribute';

    /**
     * @var CreateAttributeType
     */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new CreateAttributeType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    public function testBuildForm()
    {
        $fields = [
            [
                'code',
                'text',
                ['label' => 'orob2b.attribute.code.label']
            ],
            [
                'type',
                AttributeTypeType::NAME,
                ['label' => 'orob2b.attribute.type.label']
            ],
            [
                'localized',
                'checkbox',
                ['label' => 'orob2b.attribute.localized.label', 'required' => false]
            ]
        ];

        $formBuilder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        foreach ($fields as $index => $field) {
            $formBuilder->expects($this->at($index))
                ->method('add')
                ->with($field[0], $field[1], $field[2])
                ->will($this->returnSelf());
        }

        $this->formType->buildForm($formBuilder, []);
    }

    public function testSetDefaultOptions()
    {
        $expectedDefaults = [
            'data_class' => self::DATA_CLASS,
            'validation_groups' => ['Create']
        ];

        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($expectedDefaults);

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(CreateAttributeType::NAME, $this->formType->getName());
    }
}
