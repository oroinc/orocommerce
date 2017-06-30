<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;

class ProcessUnitPrecisionsStub extends ProcessUnitPrecisions
{
    public function handleUnitPrecisions(array $requestData)
    {
        return [
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
                    "data" => [
                        "type" => "productunitprecisions",
                        "id" => "1",
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
        ];
    }

    public function setContext($context)
    {
        $this->context = $context;
    }
}
