<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Helper;

use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizer;

class AmountNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AmountNormalizer
     */
    private $amountNormalizer;

    protected function setUp()
    {
        $this->amountNormalizer = new AmountNormalizer();
    }

    /**
     * @dataProvider normalizeDataProvider
     *
     * @param int|float|string|bool $amount
     * @param int                   $expected
     */
    public function testNormalize($amount, $expected)
    {
        $actual = $this->amountNormalizer->normalize($amount);

        static::assertSame($expected, $actual);
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
