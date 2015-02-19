<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AttributeBundle\Form\Type\SelectAttributeTypeType;
use OroB2B\Bundle\AttributeBundle\Form\Type\MultiSelectAttributeTypeType;

class MultiSelectAttributeTypeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultiSelectAttributeTypeType
     */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new MultiSelectAttributeTypeType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['multiple' => true]);

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(MultiSelectAttributeTypeType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(SelectAttributeTypeType::NAME, $this->formType->getParent());
    }
}
