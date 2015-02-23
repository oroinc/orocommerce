<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AttributeBundle\Form\Type\CheckboxFallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocalizedMultiselectCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\OptionsCollectionType;

class LocalizedMultiselectCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalizedMultiselectCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new LocalizedMultiselectCollectionType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'options' => [
                    'is_default_type' => 'checkbox',
                    'value_type' => CheckboxFallbackValueType::NAME,
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
        $this->assertEquals(LocalizedMultiselectCollectionType::NAME, $this->type->getName());
    }
}
