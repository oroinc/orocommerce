<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Model;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;

class TaxBaseExclusionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testProperties(array $data, array $replaceWith)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $exclusion = new TaxBaseExclusion($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $exclusion->offsetGet($key));
            $this->assertEquals($value, $propertyAccessor->getValue($exclusion, $key));
        }

        foreach ($replaceWith as $key => $value) {
            $propertyAccessor->setValue($exclusion, $key, $value);
            $this->assertEquals($value, $exclusion->offsetGet($key));
            $this->assertEquals($value, $propertyAccessor->getValue($exclusion, $key));
        }

        $this->assertNull($exclusion->getRegionText());
    }

    public function propertiesDataProvider(): array
    {
        return [
            [['country' => 'US'], ['country' => 'CA']],
            [['region' => 'US-AL'], ['region' => 'CA-QC']],
            [['option' => 'origin'], ['option' => 'destination']],
        ];
    }

    public function testAddInvalidOptionSetter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option values is "val", one of "destination,origin" allowed');

        $exclusion = new TaxBaseExclusion();
        $exclusion->setOption('val');
    }
}
