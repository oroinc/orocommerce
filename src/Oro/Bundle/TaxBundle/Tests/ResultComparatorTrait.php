<?php

namespace Oro\Bundle\TaxBundle\Tests;

use Brick\Math\BigNumber;

use Oro\Bundle\TaxBundle\Model\AbstractResult;
use Oro\Bundle\TaxBundle\Model\Result;

/**
 * @method void assertEquals($expected, $actual, $message = '')
 * @method void assertTrue($condition, $message = '')
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
     * @param Result|array $actual
     */
    protected function compareResult($expected, $actual)
    {
        $expected = $this->extractScalarValues($expected);
        $actual = $this->extractScalarValues($actual);

        if (!$expected) {
            $this->assertEquals([], $actual);

            return;
        }

        $this->assertEquals($expected, $expected);
    }
}
