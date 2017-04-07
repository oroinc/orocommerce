<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Helper;

use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizer;

class AmountNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider normalizeDataProvider
     *
     * @param mixed $amount
     * @param int $expected
     */
    public function testNormalize($amount, $expected)
    {
        $actual = AmountNormalizer::normalize($amount);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        return [
            [10, 1000],
            [10.01, 1001],
            ['10', 1000],
            ['10.01', 1001],
            [null, 0],
            ['', 0],
            [false, 0],
        ];
    }
}
