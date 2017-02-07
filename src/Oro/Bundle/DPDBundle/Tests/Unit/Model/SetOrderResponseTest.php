<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use InvalidArgumentException;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;

class SetOrderResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider evaluateDataProvider
     *
     * @param array $values
     * @param $expectedResult
     */
    public function testEvaluate(array $values, $expectedResult)
    {
        $response = new SetOrderResponse();
        $this->assertFalse($response->isSuccessful());
        $response->parse($values);
        $this->assertEquals(
            $expectedResult,
            [
                $response->isSuccessful(),
                $response->getTimeStamp(),
                $response->getLabelPDF(),
                $response->getParcelNumbers(),
            ]
        );
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            'one_parcel_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'LabelResponse' => [
                        'LabelPDF' => base64_encode('pdf data'),
                        'LabelDataList' => [
                            [
                                'YourInternalID' => 'internal id',
                                'ParcelNo' => 'a number',
                            ],
                        ],
                    ],
                ],
                'expectedResult' => [
                    true,
                    '2017-02-06T17:35:54.978392+01:00',
                    'pdf data',
                    [
                        'internal id' => 'a number',
                    ],
                ],
            ],
            'two_parcel_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'LabelResponse' => [
                        'LabelPDF' => base64_encode('pdf data'),
                        'LabelDataList' => [
                            [
                                'YourInternalID' => 'internal id 1',
                                'ParcelNo' => 'a number',
                            ],
                            [
                                'YourInternalID' => 'internal id 2',
                                'ParcelNo' => 'another number',
                            ],
                        ],
                    ],
                ],
                'expectedResult' => [
                    true,
                    '2017-02-06T17:35:54.978392+01:00',
                    'pdf data',
                    [
                        'internal id 1' => 'a number',
                        'internal id 2' => 'another number',
                    ],
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
        $response = new SetOrderResponse();
        $this->assertFalse($response->isSuccessful());
        $response->parse($values);
    }

    /**
     * @return array
     */
    public function evaluateThrowingDataProvider()
    {
        return [
            'no_label_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                ],
            ],
            'no_label_pdf_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'LabelResponse' => [
                        'LabelDataList' => [
                            [
                                'YourInternalID' => 'internal id',
                                'ParcelNo' => 'a number',
                            ],
                        ],
                    ],
                ],
            ],
            'no_label_data_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'LabelResponse' => [
                        'LabelPDF' => base64_encode('pdf data'),
                    ],
                ],
            ],
            'no_label_data_internal_id_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'LabelResponse' => [
                        'LabelPDF' => base64_encode('pdf data'),
                        'LabelDataList' => [
                            [
                                'ParcelNo' => 'a number',
                            ],
                        ],
                    ],
                ],
            ],
            'no_label_data_parcel_no_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
                    'LabelResponse' => [
                        'LabelPDF' => base64_encode('pdf data'),
                        'LabelDataList' => [
                            [
                                'YourInternalID' => 'internal id',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
