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
        $numberCallback = function ($number) {
            if ($number instanceof BigNumber) {
                return (string)$number;
            }

            return $number;
        };

        if ($resultElement instanceof AbstractResult) {
            $resultElement = $resultElement->getArrayCopy();
        } else {
            return array_map(
                function ($result) use ($numberCallback) {
                    if ($result instanceof AbstractResult) {
                        return array_map($numberCallback, $result->getArrayCopy());
                    }

                    return $result;
                },
                $resultElement
            );
        }

        return array_map($numberCallback, $resultElement);
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
                $this->extractScalarValues($actualValue)
            );
        }

        if (!$expected) {
            $this->assertEquals($expected, $actual->getArrayCopy());
        }
    }
}
