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
            if (strpos($field, 'text.') === 0) {
                $key = substr($field, 5);

                usort($data, function($a, $b) use ($key, $sort) {
                    if ($sort === SearchSorterExtension::DIRECTION_DESC) {
                        $result = strcmp($b[$key], $a[$key]);
                    } else {
                        $result = strcmp($a[$key], $b[$key]);
                    }
                    return $result;

                });
            }
        }

        return $data;
    }

}
