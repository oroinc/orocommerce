<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\Options;

use OroB2B\Bundle\AttributeBundle\AttributeType\Select;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Type\SelectAttributeTypeType;

class SelectAttributeTypeTypeTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'OroB2B\Bundle\AttributeBundle\Entity\AttributeOption';

    /**
     * @var SelectAttributeTypeType
     */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new SelectAttributeTypeType();
        $this->formType->setEntityClass(self::ENTITY_CLASS);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['attribute']);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['class' => self::ENTITY_CLASS, 'property' => 'value']);
        $resolver->expects($this->once())
            ->method('setNormalizers')
            ->with($this->isType('array'))
            ->willReturnCallback([$this, 'assertNormalizers']);

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * @param array $normalizers
     */
    public function assertNormalizers(array $normalizers)
    {
        $this->assertArrayHasKey('query_builder', $normalizers);

        /** @var \Closure $normalizerCallback */
        $normalizerCallback = $normalizers['query_builder'];
        $this->assertInstanceOf('\Closure', $normalizerCallback);

        $attribute = new Attribute();
        $attribute->setCode('select_attribute')
            ->setType(Select::NAME);

        $options = new Options();
        $options->set('attribute', $attribute);

        /** @var \Closure $queryBuilderCallback */
        $queryBuilderCallback = $normalizerCallback($options);
        $this->assertInstanceOf('\Closure', $queryBuilderCallback);

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('OroB2B\Bundle\AttributeBundle\Entity\Repository\AttributeOptionRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createAttributeOptionsQueryBuilder')
            ->with($attribute)
            ->willReturn($queryBuilder);

        $this->assertEquals($queryBuilder, $queryBuilderCallback($repository));
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Attribute", "DateTime" given
     */
    public function testSetDefaultOptionsInvalidAttribute()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setNormalizers')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $normalizers) {
                    $options = new Options();
                    $options->set('attribute', new \DateTime());

                    /** @var \Closure $normalizerCallback */
                    $normalizerCallback = $normalizers['query_builder'];
                    $normalizerCallback($options);
                }
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(SelectAttributeTypeType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }
}
