<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $resultItem = $this->createResultModel();

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $resultItem->getTotal());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $resultItem->getShipping());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $resultItem->getTaxes());

        $this->assertEquals($this->createTotal(), $resultItem->getTotal());
        $this->assertEquals($this->createShipping(), $resultItem->getShipping());
        $this->assertEquals($this->createTaxes(), $resultItem->getTaxes());

    }

    /**
     * @return Result
     */
    protected function createResultModel()
    {
        return new Result(
            $this->createTotal(),
            $this->createShipping(),
            $this->createTaxes()
        );
    }

    /**
     * @return ResultElement
     */
    protected function createTotal()
    {
        return new ResultElement(1, 2, 3, 4);
    }

    /**
     * @return ResultElement
     */
    protected function createShipping()
    {
        return new ResultElement(5, 6, 7, 8);
    }

    /**
     * @return ArrayCollection
     */
    protected function createTaxes()
    {
        return new ArrayCollection(['test tax']);
    }
}
