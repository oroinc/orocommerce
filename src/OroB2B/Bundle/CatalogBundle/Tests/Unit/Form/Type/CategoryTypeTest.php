<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;

class CategoryTypeTest extends \PHPUnit_Framework_TestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\CatalogBundle\Entity\Category';
    const PRODUCT_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /**
     * @var CategoryType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new CategoryType();
        $this->type->setDataClass(self::DATA_CLASS);
        $this->type->setProductClass(self::PRODUCT_CLASS);
    }

    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'parentCategory',
                EntityIdentifierType::NAME,
                ['class' => self::DATA_CLASS, 'multiple' => false]
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

        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'appendProducts',
                EntityIdentifierType::NAME,
                [
                    'class'    => self::PRODUCT_CLASS,
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'removeProducts',
                EntityIdentifierType::NAME,
                [
                    'class'    => self::PRODUCT_CLASS,
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                ]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => self::DATA_CLASS,
                    'intention' => 'category',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryType::NAME, $this->type->getName());
    }
}
