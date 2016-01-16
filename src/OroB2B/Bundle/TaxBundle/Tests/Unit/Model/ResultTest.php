<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $result = $this->createResultModel();

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $result->getTotal());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $result->getShipping());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $result->getTaxes());

        $this->assertEquals($this->createTotal(), $result->getTotal());
        $this->assertEquals($this->createShipping(), $result->getShipping());
        $this->assertEquals($this->createTaxes(), $result->getTaxes());

        $this->assertCount(3, $result);
        $expected = [
            'total' => $this->createTotal(),
            'shipping' => $this->createShipping(),
            'taxes' => $this->createTaxes(),
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
            ]
        );
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
     * @return ArrayCollection
     */
    protected function createTaxes()
    {
        return new ArrayCollection(['test tax']);
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
                ]
            )
        );
    }

    public function testTaxesDropped()
    {
        $result = $this->createResultModel();

        /** @var Result $newResult */
        $newResult = unserialize(serialize($result));
        $this->assertEquals([], $newResult->getTaxes());
    }
}
