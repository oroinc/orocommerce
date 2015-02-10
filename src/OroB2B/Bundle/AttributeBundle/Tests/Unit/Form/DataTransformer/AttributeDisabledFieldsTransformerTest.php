<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\AttributeBundle\AttributeType\String;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\AttributeDisabledFieldsTransformer;

class AttributeDisabledFieldsTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeDisabledFieldsTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new AttributeDisabledFieldsTransformer();
    }

    /**
     * @param array $model
     * @param array $view
     * @dataProvider transformDataProvider
     */
    public function testTransformAndReverseTransform(array $model, array $view)
    {
        $this->assertEquals($view, $this->transformer->transform($model));
        $this->assertEquals($model, $this->transformer->reverseTransform($view));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'empty' => [
                'model' => [],
                'view' => [],
            ],
            'partial' => [
                'model' => [
                    'code' => 'test',
                    'localized' => true,
                ],
                'view' => [
                    'code' => 'test',
                    'codeDisabled' => 'test',
                    'localized' => true,
                ],
            ],
            'full' => [
                'model' => [
                    'code' => 'test',
                    'type' => String::NAME,
                    'localized' => true,
                ],
                'view' => [
                    'code' => 'test',
                    'codeDisabled' => 'test',
                    'type' => String::NAME,
                    'typeDisabled' => String::NAME,
                    'localized' => true,
                ],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "DateTime" given
     */
    public function testTransformException()
    {
        $this->transformer->transform(new \DateTime());
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "DateTime" given
     */
    public function testReverseTransformException()
    {
        $this->transformer->reverseTransform(new \DateTime());
    }
}
