<?php

namespace OroB2B\Bundle\TaxBundle\Tests;

use Brick\Math\BigNumber;

use OroB2B\Bundle\TaxBundle\Model\AbstractResult;
use OroB2B\Bundle\TaxBundle\Model\Result;

/**
 * @method void assertEquals($expected, $actual, $message = null)
 */
trait ResultComparatorTrait
{
    /**
     * @param BigNumber[]|string[]|AbstractResult $resultElement
     * @return array
     */
    protected function extractScalarValues($resultElement)
    {
        if (is_string($resultElement)) {
            return $resultElement;
        }

        if ($resultElement instanceof BigNumber) {
            return (string)$resultElement;
        }

        if ($resultElement instanceof AbstractResult) {
            $resultElement = $resultElement->getArrayCopy();
        }

        if (is_array($resultElement)) {
            foreach ($resultElement as &$element) {
                $element = $this->extractScalarValues($element);
            }
        }

        return $resultElement;
    }

    /**
     * @param Result|array $expected
     * @param Result $actual
     */
    protected function compareResult($expected, Result $actual)
    {
        foreach ($expected as $key => $expectedValue) {
            $this->assertEquals(true, $actual->offsetExists($key), $key);
            $actualValue = $actual->offsetGet($key);

            $this->assertEquals(
                $this->extractScalarValues($expectedValue),
                $this->extractScalarValues($actualValue),
                $key
            );
        }

        if (!$expected) {
            $this->assertEquals($expected, $actual->getArrayCopy());
        }
    }
}
