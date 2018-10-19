<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Helper;

use Oro\Bundle\RedirectBundle\Helper\UrlParameterHelper;

class UrlParameterHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider paramProvider
     * @param array $inputParams
     * @param array $expectedParams
     */
    public function testNormalizeNumericTypes($inputParams, $expectedParams)
    {
        UrlParameterHelper::normalizeNumericTypes($inputParams);
        $this->assertSame($expectedParams, $inputParams);
    }

    /**
     * @return array
     */
    public function paramProvider()
    {
        return [
            [
                [
                    'id' => '324',
                    'type' => 'label'
                ],
                [
                    'id' => 324,
                    'type' => 'label',
                ]
            ],
            [
                [
                    'id' => 324,
                    'type' => 90
                ],
                [
                    'id' => 324,
                    'type' => 90,
                ]
            ],
            [
                [
                    'id' => '1460',
                    'type' => '500',
                    'category' => '900',
                ],
                [
                    'id' => 1460,
                    'type' => 500,
                    'category' => 900,
                ]
            ],
            [
                [
                    'id' => 0,
                    'type' => 'default',
                    'category' => [
                        'id' => '54'
                    ]
                ],
                [
                    'id' => 0,
                    'type' => 'default',
                    'category' => [
                        'id' => 54
                    ]
                ]
            ],
            [
                [
                    'id' => '2',
                    'x' => '2.3',
                ],
                [
                    'id' => 2,
                    'x' => 2.3
                ]
            ]
        ];
    }
}
