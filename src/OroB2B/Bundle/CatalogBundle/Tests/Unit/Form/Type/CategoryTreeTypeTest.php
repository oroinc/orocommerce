<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;

class CategoryTreeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryTreeType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new CategoryTreeType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'class'    => 'OroB2BCatalogBundle:Category',
                    'multiple' => false,
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryTreeType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityIdentifierType::NAME, $this->type->getParent());
    }
}
