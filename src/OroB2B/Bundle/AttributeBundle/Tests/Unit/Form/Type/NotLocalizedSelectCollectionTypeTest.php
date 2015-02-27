<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\NotLocalizedSelectCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\OptionsCollectionType;

class NotLocalizedSelectCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NotLocalizedSelectCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new NotLocalizedSelectCollectionType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'options' => [
                    'is_default_type' => 'hidden',
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
        $this->assertEquals(NotLocalizedSelectCollectionType::NAME, $this->type->getName());
    }
}
