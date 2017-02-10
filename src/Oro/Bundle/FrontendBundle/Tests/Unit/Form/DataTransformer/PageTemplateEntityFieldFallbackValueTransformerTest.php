<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendBundle\Form\DataTransformer\PageTemplateEntityFieldFallbackValueTransformer;

class PageTemplateEntityFieldFallbackValueTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PageTemplateEntityFieldFallbackValueTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new PageTemplateEntityFieldFallbackValueTransformer('route_name');
    }

    public function testTransform()
    {
        $value = new EntityFieldFallbackValue();
        $value->setArrayValue(['route_name' => 'Some value']);
        $this->transformer->transform($value);
        $this->assertEquals('Some value', $value->getScalarValue());
    }

    public function testReverseTransform()
    {
        $value = 'value';
        $this->assertEquals($value, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformEntityFieldFallbackValue()
    {
        $value = new EntityFieldFallbackValue();
        $value->setScalarValue('value');

        $this->transformer->reverseTransform($value);

        $this->assertEquals(['route_name' => 'value'], $value->getArrayValue());
        $this->assertNull($value->getScalarValue());
    }
}
