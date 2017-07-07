<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

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
                    "productType" => "simple",
                    "featured" => true
                ],
                "relationships" => [
                    "primaryUnitPrecision" => [
                        "data" => [
                            "type" => "productunitprecisions",
                            "id" => "primary-unit-id"
                        ],
                    ],
                    "unitPrecisions" => [
                        "data" => [
                            0 => [
                                "type" => "productunitprecisions",
                                "id" => "unit-precision-1"
                            ],
                            1 => [
                                "type" => "productunitprecisions",
                                "id" => "unit-precision-2"
                            ]
                        ]
                    ]
                ]
            ],
            "included" => [
                0 => [
                    "type" => "productunitprecisions",
                    "id" => "primary-unit-id",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "2",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => "item"
                            ]
                        ]
                    ]
                ],
                1 => [
                    "type" => "productunitprecisions",
                    "id" => "unit-precision-1",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "2",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => "set"
                            ]
                        ]
                    ]
                ],
                2 => [
                    "type" => "productunitprecisions",
                    "id" => "unit-precision-2",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "2",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => "each"
                            ]
                        ]
                    ]
                ],
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
        $requestData['included'][0]['relationships']['unit']['data']['id'] = $unitCode;

        return $requestData;
    }

    /**
     * @param array $requestData
     * @param string $unitCode
     * @return array
     */
    public static function setWrongUnitCode(array $requestData, $unitCode)
    {
        $requestData['included'][0]['relationships']['unit']['data']['id'] = $unitCode;

        return $requestData;
    }

    public static function setSameUnit(array $requestData, $unitCode)
    {
        foreach ($requestData['included'] as &$data) {
            $data['relationships']['unit']['data']['id'] = $unitCode;
        }

        return $requestData;
    }

    /**
     * @return array
     */
    public static function createUpdateRequestData()
    {
        return [
            "data" => [
                "type" => "products",
                "attributes" => [
                    "status" => "enabled",
                    "variantFields" => [],
                    "productType" => "simple",
                    "featured" => true
                ],
                "relationships" => [
                    "primaryUnitPrecision" => [
                        "data" => [
                            "type" => "productunitprecisions",
                            "id" => "1"
                        ],
                    ],
                    "unitPrecisions" => [
                        "data" => [
                            0 => [
                                "type" => "productunitprecisions",
                                "id" => "2"
                            ],
                            1 => [
                                "type" => "productunitprecisions",
                                "id" => "3"
                            ]
                        ]
                    ]
                ]
            ],
            "included" => [
                0 => [
                    "meta" => [
                        "update" => true,
                    ],
                    "type" => "productunitprecisions",
                    "id" => "1",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "3",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => "item"
                            ]
                        ]
                    ]
                ],
                1 => [
                    "meta" => [
                        "update" => true,
                    ],
                    "type" => "productunitprecisions",
                    "id" => "2",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "4",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => "set"
                            ]
                        ]
                    ]
                ],
                2 => [
                    "meta" => [
                        "update" => true,
                    ],
                    "type" => "productunitprecisions",
                    "id" => "3",
                    "attributes" => [
                        "precision" => "0",
                        "conversionRate" => "5",
                        "sell" => "1"
                    ],
                    "relationships" => [
                        "unit" => [
                            "data" => [
                                "type" => "productunits",
                                "id" => "each"
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * @param $codes
     * @return array
     */
    public static function getProducUnitPrecisions($codes)
    {
        $productUnitPrecisions = [];
        foreach ($codes as $code) {
            $productUnitPrecision = new ProductUnitPrecision();
            $productUnit = new ProductUnit();
            $productUnit->setCode($code);
            $productUnitPrecision->setUnit($productUnit);

            $productUnitPrecisions[] = $productUnitPrecision;
        }

        return $productUnitPrecisions;
    }
}
