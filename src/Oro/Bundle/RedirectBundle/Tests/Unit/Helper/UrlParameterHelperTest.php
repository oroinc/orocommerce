<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Helper;

use Oro\Bundle\RedirectBundle\Helper\UrlParameterHelper;

class UrlParameterHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider paramProvider
     */
    public function testNormalizeNumericTypes(array $inputParams, array $expectedParams)
    {
        UrlParameterHelper::normalizeNumericTypes($inputParams);
        $this->assertSame($expectedParams, $inputParams);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function paramProvider(): array
    {
        return [
            [
                [
                    'id' => '000324',
                    'type' => 'label'
                ],
                [
                    'id' => '000324',
                    'type' => 'label',
                ]
            ],
            [
                [
                    'id' => '123e1',
                    'type' => 'label'
                ],
                [
                    'id' => '123e1',
                    'type' => 'label',
                ]
            ],
            [
                [
                    'id' => '0e1',
                    'type' => 'label'
                ],
                [
                    'id' => '0e1',
                    'type' => 'label',
                ]
            ],
            [
                [
                    'id' => 0e1,
                    'type' => 'label'
                ],
                [
                    'id' => 0e1,
                    'type' => 'label',
                ]
            ],
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
