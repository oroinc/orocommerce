<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $result = $this->createResultModel();

        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\ResultElement', $result->getTotal());
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\ResultElement', $result->getShipping());
        $this->assertInternalType('array', $result->getTaxes());
        $this->assertInternalType('array', $result->getItems());

        $this->assertEquals($this->createTotal(), $result->getTotal());
        $this->assertEquals($this->createShipping(), $result->getShipping());
        $this->assertEquals($this->createTaxes(), $result->getTaxes());
        $this->assertEquals($this->createItemsResult(), $result->getItems());

        $this->assertCount(4, $result);
        $expected = [
            'total' => $this->createTotal(),
            'shipping' => $this->createShipping(),
            'taxes' => $this->createTaxes(),
            'items' => $this->createItemsResult(),
        ];

        foreach ($result as $key => $value) {
            $this->assertArrayHasKey($key, $expected);
            $this->assertEquals($expected[$key], $value);
        }
    }

    /**
     * @return Result
     */
    protected function createResultModel()
    {
        return new Result(
            [
                Result::TOTAL => $this->createTotal(),
                Result::SHIPPING => $this->createShipping(),
                Result::TAXES => $this->createTaxes(),
                Result::ITEMS => $this->createItemsResult(),
            ]
        );
    }

    /**
     * @return array
     */
    protected function createItemsResult()
    {
        return [new Result()];
    }

    /**
     * @return ResultElement
     */
    protected function createTotal()
    {
        return ResultElement::create(1, 2, 3, 4);
    }

    /**
     * @return ResultElement
     */
    protected function createShipping()
    {
        return ResultElement::create(5, 6, 7, 8);
    }

    /**
     * @return array
     */
    protected function createTaxes()
    {
        return ['test tax'];
    }

    public function testConstruct()
    {
        $this->assertEquals(
            $this->createResultModel(),
            new Result(
                [
                    Result::TOTAL => new ResultElement(
                        [
                            ResultElement::INCLUDING_TAX => 1,
                            ResultElement::EXCLUDING_TAX => 2,
                            ResultElement::TAX_AMOUNT => 3,
                            ResultElement::ADJUSTMENT => 4,
                        ]
                    ),
                    Result::SHIPPING => $this->createShipping(),
                    Result::TAXES => $this->createTaxes(),
                    Result::ITEMS => $this->createItemsResult(),
                ]
            )
        );
    }

    public function testItemsDropped()
    {
        $result = $this->createResultModel();

        /** @var Result $newResult */
        $newResult = unserialize(serialize($result));
        $this->assertEquals([], $newResult->getItems());
    }

    public function testSerializeWithoutItems()
    {
        $result = $this->createResultModel();
        $result->unsetOffset(Result::TAXES);

        $newResult = unserialize(serialize($result));
        $this->assertEquals([], $newResult->getItems());
    }

    public function testLock()
    {
        $result = $this->createResultModel();
        $this->assertFalse($result->isResultLocked());

        $result->lockResult();
        $this->assertTrue($result->isResultLocked());

        $result->unlockResult();
        $this->assertFalse($result->isResultLocked());
    }
}
