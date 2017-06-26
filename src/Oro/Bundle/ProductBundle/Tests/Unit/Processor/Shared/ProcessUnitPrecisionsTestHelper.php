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

    public static function setPrimaryUnitCode(array $requestData, $unitCode)
    {
        $requestData['data']['relationships']['primaryUnitPrecision']['unit_code'] = $unitCode;

        return $requestData;
    }

    public static function setWrongUnitCode(array $requestData, $unitCode)
    {
        $requestData['data']['relationships']['unitPrecisions']['data'][0]['unit_code'] = $unitCode;

        return $requestData;
    }

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
