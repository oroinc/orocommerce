<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared;


class ProcessUnitPrecisionsTestHelper
{
    /**
     * @return array
     */
    public static function createRequestData()
    {
        return [
            "data" => [
                "type" => "products",
                "attributes" => [
                    "sku" => "test-api",
                    "status" => "enabled",
                    "variantFields" => [],
                    "createdAt" => "2017-06-13T07:12:06Z",
                    "updatedAt" => "2017-06-13T07:12:31Z",
                    "productType" => "simple",
                    "featured" => true
                ],
                "relationships" => [
                    "primaryUnitPrecision" => [
                        "unit_code" => "each",
                    ],
                    "unitPrecisions" => [
                        "data" => [
                            0 => [
                                "type" => "productunitprecisions",
                                "unit_code" => "each",
                                "unit_precision" => "0",
                                "conversion_rate" => "2",
                                "sell" => "1"
                            ],
                            1 => [
                                "type" => "productunitprecisions",
                                "unit_code" => "item",
                                "unit_precision" => "0",
                                "conversion_rate" => "2",
                                "sell" => "1"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    /**
     * @return array
     */
    public static function createNormalizedRequestData()
    {
        return [
            "sku" => "test-api",
            "status" => "enabled",
            "variantFields" => [],
            "createdAt" => "2017-06-13T07:12:06Z",
            "updatedAt" => "2017-06-13T07:12:31Z",
            "productType" => "simple",
            "featured" => true,
            "primaryUnitPrecision" => [
                "type" => "productunitprecisions",
                "id" => "1",
            ],
            "unitPrecisions" => [
                0 => [
                    "type" => "productunitprecisions",
                    "id" => "2"
                ],
                1 => [
                    "type" => "productunitprecisions",
                    "id" => "3"
                ]
            ]
        ];
    }

    /**
     * @param array $requestData
     * @param string $unitCode
     * @return array
     */
    public static function setPrimaryUnitCode(array $requestData, $unitCode)
    {
        $requestData['data']['relationships']['primaryUnitPrecision']['unit_code'] = $unitCode;

        return $requestData;
    }

    /**
     * @param array $requestData
     * @param string $unitCode
     * @return array
     */
    public static function setWrongUnitCode(array $requestData, $unitCode)
    {
        $requestData['data']['relationships']['unitPrecisions']['data'][0]['unit_code'] = $unitCode;

        return $requestData;
    }

    /**
     * @return array
     */
    public static function createRequestDataSameUnitCodes()
    {
        return [
            "data" => [
                "type" => "products",
                "attributes" => [
                    "sku" => "test-api",
                    "status" => "enabled",
                    "variantFields" => [],
                    "createdAt" => "2017-06-13T07:12:06Z",
                    "updatedAt" => "2017-06-13T07:12:31Z",
                    "productType" => "simple",
                    "featured" => true
                ],
                "relationships" => [
                    "primaryUnitPrecision" => [
                        "unit_code" => "each",
                    ],
                    "unitPrecisions" => [
                        "data" => [
                            0 => [
                                "type" => "productunitprecisions",
                                "unit_code" => "each",
                                "unit_precision" => "0",
                                "conversion_rate" => "2",
                                "sell" => "1"
                            ],
                            1 => [
                                "type" => "productunitprecisions",
                                "unit_code" => "each",
                                "unit_precision" => "0",
                                "conversion_rate" => "2",
                                "sell" => "1"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public static function createRequestDataWrongPrimaryUnit()
    {
        return [
            "data" => [
                "type" => "products",
                "attributes" => [
                    "sku" => "test-api",
                    "status" => "enabled",
                    "variantFields" => [],
                    "createdAt" => "2017-06-13T07:12:06Z",
                    "updatedAt" => "2017-06-13T07:12:31Z",
                    "productType" => "simple",
                    "featured" => true
                ],
                "relationships" => [
                    "primaryUnitPrecision" => [
                        "unit_code" => "item",
                    ],
                    "unitPrecisions" => [
                        "data" => [
                            0 => [
                                "type" => "productunitprecisions",
                                "unit_code" => "each",
                                "unit_precision" => "0",
                                "conversion_rate" => "2",
                                "sell" => "1"
                            ],
                            1 => [
                                "type" => "productunitprecisions",
                                "unit_code" => "set",
                                "unit_precision" => "0",
                                "conversion_rate" => "2",
                                "sell" => "1"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
