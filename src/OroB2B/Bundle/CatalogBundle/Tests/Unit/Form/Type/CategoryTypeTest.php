<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;

class CategoryTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new CategoryType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'parentCategory',
                'oro_entity_identifier',
                ['class' => 'OroB2B\Bundle\CatalogBundle\Entity\Category', 'multiple' => false]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'orob2b.catalog.category.titles.label',
                    'required' => false,
                    'options' => ['constraints' => [new NotBlank()]],
                ]
            )
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
                    'data_class' => 'OroB2B\Bundle\CatalogBundle\Entity\Category',
                    'intention' => 'category',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryType::NAME, $this->type->getName());
    }
}
