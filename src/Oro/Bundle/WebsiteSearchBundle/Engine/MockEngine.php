<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

/**
 * Mock data engine.
 */
class MockEngine implements EngineV2Interface
{
    /**
     * @param Query $query
     * @param array $context
     * @return Result
     */
    public function search(Query $query, $context = [])
    {
        $data = [
            [
                'id'               => 1,
                'sku'              => '01C82',
                'name'             => 'Canon 5D EOS',
                'shortDescription' => 'Small description of another good product from our shop.',
                'minimum_price'    => '1299.00',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '1299.00'
                ],
                'image'            => null

            ],
        ];

        return new Result(new Query(), $data);
    }
}
