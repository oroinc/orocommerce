<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Extension\Sorter\SearchSorterExtension;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

/**
 * Mock data engine.
 * This engine code is demonstrating a very basic control flow
 * of a V2 engine: it retrieves a Query, mimics Query execution
 * and returns data, complying the requested parameters in the Query.
 */
class ExampleMockEngine implements EngineV2Interface
{
    /**
     * @param Query $query
     * @param array $context
     * @return Result
     */
    public function search(Query $query, $context = [])
    {
        // a real engine would decompile the Query object, translate
        // it into its own DBMS system query, run it and postprocess results.
        $fullData = $this->getFullDataset();

        // we are only wanting the results to correspond with
        // the fields (columns) that have been explicitely "selected",
        // using the new Query addSelect() feature.
        $selectedColumns = $query->getSelect();

        // let's get the keys that we want to have in results
        $selectedColumns = $this->getFieldsToBeSelected($selectedColumns);

        // parsing the full dataset and eliminating all fields
        // that have not been requested.
        $result = $this->extractSelectedFields($fullData, $selectedColumns);

        // order rows by ordering specified in query
        $result = $this->getOrderedData($query, $result);

        return new Result($query, $result);
    }

    /**
     * @return array
     */
    private function getFullDataset()
    {
        return [
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
            [
                'id'               => 2,
                'sku'              => '6VC22',
                'name'             => 'Bluetooth Barcode Scanner',
                'shortDescription' => 'This innovative Bluetooth barcode scanner allows easy bar code transmission...',
                'minimum_price'    => '340.00',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '340.00'
                ],
                'image'            => null

            ],
            [
                'id'               => 3,
                'sku'              => '5GE27',
                'name'             => 'Pricing Labeler',
                'shortDescription' => 'This pricing labeler is easy to use and comes with...',
                'minimum_price'    => '165.00',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '165.00'
                ],
                'image'            => null

            ],
            [
                'id'               => 4,
                'sku'              => '9GQ28',
                'name'             => 'NFC Credit Card Reader',
                'shortDescription' => 'This NFC credit card reader accepts PIN-based...',
                'minimum_price'    => '240.00',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '240.00'
                ],
                'image'            => null

            ],
            [
                'id'               => 5,
                'sku'              => '8BC37',
                'name'             => 'Colorful Floral Women’s Scrub Top',
                'shortDescription' => 'This bright, colorful women’s scrub top is not only...',
                'minimum_price'    => '14.95',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '14.95'
                ],
                'image'            => null

            ],
            [
                'id'               => 6,
                'sku'              => '5TJ23',
                'name'             => '17-inch POS Touch Screen Monitor with Card Reader',
                'shortDescription' => 'This sleek and slim 17-inch touch screen monitor is great for retail',
                'minimum_price'    => '290.00',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '290.00'
                ],
                'image'            => null

            ],
            [
                'id'               => 7,
                'sku'              => '4PJ19',
                'name'             => 'Handheld Laser Barcode Scanner',
                'shortDescription' => 'This lightweight laser handheld barcode scanner offers high performace...',
                'minimum_price'    => '190.00',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '190.00'
                ],
                'image'            => null

            ],
            [
                'id'               => 8,
                'sku'              => '7NQ22',
                'name'             => 'Storage Combination with Doors',
                'shortDescription' => 'Store and display your favorite items with this storage-display cabinet.',
                'minimum_price'    => '789.99',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '789.99'
                ],
                'image'            => null

            ],
            [
                'id'               => 9,
                'sku'              => '5GF68',
                'name'             => '300-Watt Floodlight',
                'shortDescription' => 'This 300-watt flood light provides bright and focused illumination.',
                'minimum_price'    => '35.99',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '35.99'
                ],
                'image'            => null

            ],
            [
                'id'               => 10,
                'sku'              => '8DO33',
                'name'             => 'Receipt Printer',
                'shortDescription' => 'This receipt printer uses a ribbon to transfer ink to paper',
                'minimum_price'    => '240.00',
                'product_units'    => [
                    'item' => 'item'
                ],
                'prices'           => [
                    'item_1' => '240.00'
                ],
                'image'            => null
            ],
        ];
    }

    /**
     * @param $selectedColumns
     * @return mixed
     */
    private function getFieldsToBeSelected($selectedColumns)
    {
        $result = ['id' => 'integer'];

        if (!empty($selectedColumns)) {
            foreach ($selectedColumns as &$selectedColumn) {
                list($type, $name) = Criteria::explodeFieldTypeName($selectedColumn);
                $result[$name] = $type;
            }
        }
        return $result;
    }

    /**
     * @param $fullData
     * @param $selectedColumns
     * @return array
     */
    private function extractSelectedFields($fullData, $selectedColumns)
    {
        $result = [];

        foreach ($fullData as $rowSet) {
            $resultRowSet = [];
            foreach ($rowSet as $field => $value) {
                if (isset($selectedColumns[$field])) {
                    $resultRowSet[$field] = $value;
                }
            }
            $result[] = $resultRowSet;
        }

        return $result;
    }

    /**
     * @param Query $query
     * @param array $data
     * @return array
     */
    private function getOrderedData(Query $query, array $data) {
        foreach($query->getCriteria()->getOrderings() as $field => $sort) {
            list($type, $key) = Criteria::explodeFieldTypeName($field);

            usort($data, function($a, $b) use ($key, $sort) {
                if ($sort === SearchSorterExtension::DIRECTION_DESC) {
                    $result = strcmp($b[$key], $a[$key]);
                } else {
                    $result = strcmp($a[$key], $b[$key]);
                }
                return $result;

            });
        }

        return $data;
    }
}
