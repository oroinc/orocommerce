<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AttributeBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\NotLocalizedMultiselectCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\OptionsCollectionType;

class MultiselectCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NotLocalizedMultiselectCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new NotLocalizedMultiselectCollectionType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'options' => [
                    'is_default_type' => 'checkbox',
                    'value_type' => FallbackValueType::NAME,
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
        $this->assertEquals(NotLocalizedMultiselectCollectionType::NAME, $this->type->getName());
    }
}
