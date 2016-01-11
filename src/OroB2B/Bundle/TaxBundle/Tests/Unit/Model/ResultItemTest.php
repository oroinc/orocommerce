<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\ResultItem;

class ResultItemTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $resultItem = $this->createResultItemModel();

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $resultItem->getUnit());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $resultItem->getRow());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $resultItem->getTaxes());

        $this->assertEquals($this->createUnit(), $resultItem->getUnit());
        $this->assertEquals($this->createRow(), $resultItem->getRow());
        $this->assertEquals($this->createTaxes(), $resultItem->getTaxes());


        $this->assertCount(3, $resultItem);
        $expected = [
            'unit' => $this->createUnit(),
            'row' => $this->createRow(),
            'taxes' => $this->createTaxes(),
        ];

        foreach ($resultItem as $key => $value) {
            $this->assertArrayHasKey($key, $expected);
            $this->assertEquals($expected[$key], $value);
        }
    }

    /**
     * @return ResultItem
     */
    protected function createResultItemModel()
    {
        return ResultItem::create(
            $this->createUnit(),
            $this->createRow(),
            $this->createTaxes()
        );
    }

    /**
     * @return ResultElement
     */
    protected function createUnit()
    {
        return ResultElement::create(1, 2, 3, 4);
    }

    /**
     * @return ResultElement
     */
    protected function createRow()
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
}
