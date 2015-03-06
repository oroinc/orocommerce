<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\FallbackBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedPropertyType;

class LocalizedFallbackValueCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var LocalizedFallbackValueCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->type = new LocalizedFallbackValueCollectionType($this->registry);
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedFallbackValueCollectionType::NAME, $this->type->getName());
    }

    public function testSetDefaults()
    {
        $expectedOptions = [
            'field' => 'string',
            'type' => 'text',
            'options' => [],
        ];

        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($expectedOptions);

        $this->type->setDefaultOptions($resolver);
    }

    public function testBuildForm()
    {
        $type = 'form_text';
        $options = ['key' => 'value'];
        $field = 'text';

        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                LocalizedFallbackValueCollectionType::FIELD_VALUES,
                LocalizedPropertyType::NAME,
                ['type' => $type, 'options' => $options]
            )->willReturnSelf();
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                LocalizedFallbackValueCollectionType::FIELD_IDS,
                'collection',
                ['type' => 'hidden']
            )->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViewTransformer')
            ->with(new LocalizedFallbackValueCollectionTransformer($this->registry, $field))
            ->willReturnSelf();

        $this->type->buildForm(
            $builder,
            ['type' => $type, 'options' => $options, 'field' => $field]
        );
    }
}
