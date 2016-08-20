<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;

class CategoryTypeTest extends \PHPUnit_Framework_TestCase
{
    const DATA_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';
    const PRODUCT_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';

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
            ->disableOriginalConstructor()->getMock();

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
                    'label' => 'oro.catalog.category.titles.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                $this->getOroRichTextTypeConfiguration('oro.catalog.category.short_descriptions.label')
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'longDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                $this->getOroRichTextTypeConfiguration('oro.catalog.category.long_descriptions.label')
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'appendProducts',
                EntityIdentifierType::NAME,
                ['class' => self::PRODUCT_CLASS, 'required' => false, 'mapped' => false, 'multiple' => true]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'removeProducts',
                EntityIdentifierType::NAME,
                ['class' => self::PRODUCT_CLASS, 'required' => false, 'mapped' => false, 'multiple' => true]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(6))
            ->method('add')
            ->with(
                'smallImage',
                'oro_image',
                ['label' => 'oro.catalog.category.small_image.label', 'required' => false]
            )->will($this->returnSelf());

        $builder->expects($this->at(7))
            ->method('add')
            ->with(
                'largeImage',
                'oro_image',
                ['label' => 'oro.catalog.category.large_image.label', 'required' => false]
            )->will($this->returnSelf());

        $builder->expects($this->at(8))
            ->method('add')
            ->with(
                'defaultProductOptions',
                CategoryDefaultProductOptionsType::NAME,
                ['required' => false]
            )->will($this->returnSelf());

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

    /**
     * @param string $label
     * @return array
     */
    protected function getOroRichTextTypeConfiguration($label)
    {
        return [
            'label' => $label,
            'required' => false,
            'field' => 'text',
            'type' => OroRichTextType::NAME,
            'options' => [
                'wysiwyg_options' => [
                    'statusbar' => true,
                    'resize' => true,
                    'width' => 500,
                    'height' => 200,
                    'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullscreen']),
                    'toolbar' =>
                        [reset(OroRichTextType::$toolbars[OroRichTextType::TOOLBAR_DEFAULT]) . ' | fullscreen'],
                ],
            ]
        ];
    }
}
