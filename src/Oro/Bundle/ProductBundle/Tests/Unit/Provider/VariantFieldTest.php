<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Provider\VariantField;

class VariantFieldTest extends \PHPUnit\Framework\TestCase
{
    public function testAccessors()
    {
        $variantField = new VariantField('some_field_name', 'Some label');
        $this->assertEquals('some_field_name', $variantField->getName());
        $this->assertEquals('Some label', $variantField->getLabel());
    }
}
