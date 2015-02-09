<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AttributeBundle\Form\Type\AttributeType;

class AttributeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new AttributeType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->once())
            ->method('add')
            ->with('title', 'text')
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\AttributeBundle\Entity\Attribute',
                    'intention' => 'attribute',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_attribute', $this->type->getName());
    }
}
