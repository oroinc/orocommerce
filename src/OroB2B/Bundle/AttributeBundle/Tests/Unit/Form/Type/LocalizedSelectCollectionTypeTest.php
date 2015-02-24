<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AttributeBundle\Form\Type\HiddenFallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocalizedSelectCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\OptionsCollectionType;

class LocalizedSelectCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalizedSelectCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new LocalizedSelectCollectionType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'options' => [
                    'is_default_type' => 'hidden',
                    'value_type' => HiddenFallbackValueType::NAME,
                    'required' => false,
                ]
            ]);

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(OptionsCollectionType::NAME, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedSelectCollectionType::NAME, $this->type->getName());
    }
}
