<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Model;

use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Symfony\Component\PropertyAccess\PropertyAccess;

class TaxBaseExclusionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testProperties(array $data, array $replaceWith)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $result = $this->createModel($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $result->offsetGet($key));
            $this->assertEquals($value, $propertyAccessor->getValue($result, $key));
        }

        foreach ($replaceWith as $key => $value) {
            $propertyAccessor->setValue($result, $key, $value);
            $this->assertEquals($value, $result->offsetGet($key));
            $this->assertEquals($value, $propertyAccessor->getValue($result, $key));
        }

        $this->assertNull($result->getRegionText());
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            [['country' => 'US'], ['country' => 'CA']],
            [['region' => 'US-AL'], ['region' => 'CA-QC']],
            [['option' => 'shipping_origin'], ['option' => 'destination']],
        ];
    }

    public function testAddInvalidOptionSetter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option values is "val", one of "destination,shipping_origin" allowed');

        $this->createModel()->setOption('val');
    }

    /**
     * @param array $data
     * @return TaxBaseExclusion
     */
    protected function createModel(array $data = [])
    {
        return new TaxBaseExclusion($data);
    }
}
