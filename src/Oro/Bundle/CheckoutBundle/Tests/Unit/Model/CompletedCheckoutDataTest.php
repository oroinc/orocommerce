<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model;

use Oro\Bundle\CheckoutBundle\Model\CompletedCheckoutData;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CompletedCheckoutDataTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $data = $this->createCompletedData();

        $this->assertEquals('USD', $data->getCurrency());
        $this->assertEquals(42, $data->getItemsCount());
        $this->assertInternalType('array', $data->getOrderData());
        $this->assertEquals(['entityAlias' => 'test', 'entityId' => 105], $data->getOrderData());
        $this->assertEquals('test string', $data->getStartedFrom());
        $this->assertEquals(100.2, $data->getSubtotal());
        $this->assertEquals(300.4, $data->getTotal());
    }

    public function testDefaultPropertiesValues()
    {
        $data = new CompletedCheckoutData();

        $this->assertEquals(null, $data->getCurrency());
        $this->assertEquals(0, $data->getItemsCount());
        $this->assertEquals(null, $data->getOrderData());
        $this->assertEquals(null, $data->getStartedFrom());
        $this->assertEquals(0, $data->getSubtotal());
        $this->assertEquals(0, $data->getTotal());
    }

    public function testJsonSerialize()
    {
        $data = $this->createCompletedData();
        $data->offsetSet('test', 'value');

        $newData = CompletedCheckoutData::jsonDeserialize(json_decode(json_encode($data), true));

        $this->assertEquals($data, $newData);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You cannot deserialize CompletedCheckoutData from anything, except array or null
     */
    public function testJsonDeserializeException()
    {
        CompletedCheckoutData::jsonDeserialize(new \stdClass());
    }

    /**
     * @return CompletedCheckoutData
     */
    protected function createCompletedData()
    {
        return new CompletedCheckoutData(
            [
                CompletedCheckoutData::CURRENCY => 'USD',
                CompletedCheckoutData::ITEMS_COUNT => 42,
                CompletedCheckoutData::ORDERS => [['entityAlias' => 'test', 'entityId' => 105]],
                CompletedCheckoutData::STARTED_FROM => 'test string',
                CompletedCheckoutData::SUBTOTAL => 100.2,
                CompletedCheckoutData::TOTAL => 300.4,
            ]
        );
    }
}
