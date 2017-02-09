<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendBundle\Form\DataTransformer\PageTemplateEntityFieldFallbackValueTransformer;

class PageTemplateEntityFieldFallbackValueTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testTransform()
    {
        $transformer = new PageTemplateEntityFieldFallbackValueTransformer('route_name');

        $value = 'value';
        $this->assertEquals($value, $transformer->transform($value));
    }

    public function testReverseTransform()
    {
        $transformer = new PageTemplateEntityFieldFallbackValueTransformer('route_name');

        $value = 'value';
        $this->assertEquals($value, $transformer->reverseTransform($value));
    }

    public function testReverseTransformEntityFieldFallbackValue()
    {
        $transformer = new PageTemplateEntityFieldFallbackValueTransformer('route_name');

        $value = new EntityFieldFallbackValue();
        $value->setScalarValue('value');

        $transformer->reverseTransform($value);

        $this->assertEquals(['route_name' => 'value'], $value->getScalarValue());
    }
}
