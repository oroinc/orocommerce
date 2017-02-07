<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use InvalidArgumentException;
use Oro\Bundle\DPDBundle\Model\DPDResponse;

class DPDResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider evaluateDataProvider
     *
     * @param array $values
     * @param $expectedResult
     */
    public function testEvaluate(array $values, $expectedResult)
    {
        $response = new DPDResponse();
        $this->assertFalse($response->isSuccessful());
        $response->parse($values);
        $this->assertEquals(
            $expectedResult,
            [
                $response->isSuccessful(),
                $response->getTimeStamp(),
                count($response->getErrors()),
                $response->getErrorMessagesShort(),
                $response->getErrorMessagesLong(),
            ]
        );
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            'successful_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                ],
                'expectedResult' => [
                    true,
                    '2017-02-06T17:35:54.978392+01:00',
                    0,
                    [],
                    [],
                ],
            ],
            'failed_response' => [
                'values' => [
                    'Ack' => false,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'ErrorDataList' => [
                        [
                            'ErrorID' => 1,
                            'ErrorCode' => 'AN_ERROR_CODE',
                            'ErrorMsgShort' => 'A short error msg',
                            'ErrorMsgLong' => 'A long error msg',
                        ],
                    ],
                ],
                'expectedResult' => [
                    false,
                    '2017-02-06T17:35:54.978392+01:00',
                    1,
                    ['A short error msg (ErrorID=1)'],
                    ['A long error msg (ErrorID=1)'],
                ],
            ],
            'failed_response_with_two_errors' => [
                'values' => [
                    'Ack' => false,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'ErrorDataList' => [
                        [
                            'ErrorID' => 1,
                            'ErrorCode' => 'AN_ERROR_CODE',
                            'ErrorMsgShort' => 'A short error msg',
                            'ErrorMsgLong' => 'A long error msg',
                        ],
                        [
                            'ErrorID' => 2,
                            'ErrorCode' => 'ANOTHER_ERROR_CODE',
                            'ErrorMsgShort' => 'Another short error msg',
                            'ErrorMsgLong' => 'Another long error msg',
                        ],
                    ],
                ],
                'expectedResult' => [
                    false,
                    '2017-02-06T17:35:54.978392+01:00',
                    2,
                    ['A short error msg (ErrorID=1)', 'Another short error msg (ErrorID=2)'],
                    ['A long error msg (ErrorID=1)', 'Another long error msg (ErrorID=2)'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider evaluateThrowingDataProvider
     * @expectedException InvalidArgumentException
     *
     * @param array $values
     */
    public function testEvaluateThrowing(array $values)
    {
        $response = new DPDResponse();
        $this->assertFalse($response->isSuccessful());
        $response->parse($values);
    }

    /**
     * @return array
     */
    public function evaluateThrowingDataProvider()
    {
        return [
            'empty_response' => [
                'values' => [],
            ],
            'no_ack_response' => [
                'values' => [
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'ErrorDataList' => [
                        [
                            'ErrorID' => 1,
                            'ErrorCode' => 'AN_ERROR_CODE',
                            'ErrorMsgShort' => 'A short error msg',
                            'ErrorMsgLong' => 'A long error msg',
                        ],
                    ],
                ],
            ],
            'no_timestamp_response' => [
                'values' => [
                    'Ack' => true,
                ],
            ],
        ];
    }
}
