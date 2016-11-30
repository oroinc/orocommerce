<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;

class TaxBaseExclusionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param array $data
     * @param array $replaceWith
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Option values is "val", one of "destination,shipping_origin" allowed
     */
    public function testAddInvalidOptionSetter()
    {
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
