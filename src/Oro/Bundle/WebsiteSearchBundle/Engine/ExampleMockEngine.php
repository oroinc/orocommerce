<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
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
     * Placeholder in column names that has to be replaced by language code of current localization
     *
     * @var string
     */
    private static $localizationIdPlaceholder = 'LOCALIZATION_ID';

    /**
     * @var CurrentLocalizationProvider
     */
    private $currentLocalizationProvider;

    /**
     * @var array
     */
    private $resolvedFieldsBackArray;

    /**
     * @param CurrentLocalizationProvider $currentLocalizationProvider
     */
    public function __construct(CurrentLocalizationProvider $currentLocalizationProvider)
    {
        $this->currentLocalizationProvider = $currentLocalizationProvider;
    }

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

        // resolve LOCALIZATION_ID references
        $selectedColumns = $this->resolveLocalizationPlaceholder($selectedColumns);

        // let's get the keys that we want to have in results
        $selectedColumns = $this->getFieldsToBeSelected($selectedColumns);

        // parsing the full dataset and eliminating all fields
        // that have not been requested.
        $result = $this->readDatabase($fullData, $selectedColumns);

        // revert to original field names (*_LOCALIZATION_ID)
        $result = $this->revertLocalizationPlaceholderInResults($result);

        // this essentially duplicates localized fields' values
        // trimming the _LID suffix
        $result = $this->copyPlaceholderFieldsToNormal($result);

        // this applies filter functionality to the results.
        $result = $this->applyFilters($result, $query->getCriteria());

        // order rows by ordering specified in query
        $result = $this->getOrderedData($query, $result);

        $count = count($result);

        // support pagination
        $result = $this->getPaginatedData($query, $result);

        return new Result($query, $result, $count);
    }

    /**
     * @return array
     */
    private function getFullDataset()
    {
        return [
            [
                'id'                 => 1,
                'sku'                => '01C82',
                'name_1'             => 'Canon 5D EOS',
                'shortDescription_1' => 'Small description of another good product from our shop.',
                'all_text_1'         => '01C82 canon 5s eos small description of another good product from',
                'minimum_price'      => '1299.00',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '1299.00'
                ],
                'image'              => null

            ],
            [
                'id'                 => 2,
                'sku'                => '6VC22',
                'name_1'             => 'Bluetooth Barcode Scanner',
                'shortDescription_1' => 'This innovative Bluetooth barcode scanner allows easy bar code...',
                'all_text_1'         => '6VC22 bluetooth barcode scanner this innovative bluetooth',
                'minimum_price'      => '340.00',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '340.00'
                ],
                'image'              => null

            ],
            [
                'id'                 => 3,
                'sku'                => '5GE27',
                'name_1'             => 'Pricing Labeler',
                'shortDescription_1' => 'This pricing labeler is easy to use and comes with...',
                'all_text_1'         => '5ge27 pricing labeler this pricing labeler',
                'minimum_price'      => '165.00',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '165.00'
                ],
                'image'              => null

            ],
            [
                'id'                 => 4,
                'sku'                => '9GQ28',
                'name_1'             => 'NFC Credit Card Reader',
                'shortDescription_1' => 'This NFC credit card reader accepts PIN-based...',
                'all_text_1'         => '9GQ28 nfc credit card reader accepts',
                'minimum_price'      => '240.00',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '240.00'
                ],
                'image'              => null

            ],
            [
                'id'                 => 5,
                'sku'                => '8BC37',
                'name_1'             => 'Colorful Floral Women’s Scrub Top',
                'shortDescription_1' => 'This bright, colorful women’s scrub top is not only...',
                'all_text_1'         => '8BC37 colorful floral women scrub top',
                'minimum_price'      => '14.95',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '14.95'
                ],
                'image'              => null

            ],
            [
                'id'                 => 6,
                'sku'                => '5TJ23',
                'name_1'             => '17-inch POS Touch Screen Monitor with Card Reader',
                'shortDescription_1' => 'This sleek and slim 17-inch touch screen monitor is great for retail',
                'all_text_1'         => '5TJ23 17 inch pos touch screen monitor with card reader',
                'minimum_price'      => '290.00',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '290.00'
                ],
                'image'              => null

            ],
            [
                'id'                 => 7,
                'sku'                => '4PJ19',
                'name_1'             => 'Handheld Laser Barcode Scanner',
                'shortDescription_1' => 'This lightweight laser handheld barcode scanner offers high performace...',
                'all_text_1'         => '4PJ19 handheld laser barcode scanner this lightweight',
                'minimum_price'      => '190.00',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '190.00'
                ],
                'image'              => null

            ],
            [
                'id'                 => 8,
                'sku'                => '7NQ22',
                'name_1'             => 'Storage Combination with Doors',
                'shortDescription_1' => 'Store and display your favorite items with this storage-display cabinet.',
                'all_text_1'         => '7NQ22 storage combination with doors store display',
                'minimum_price'      => '789.99',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '789.99'
                ],
                'image'              => null

            ],
            [
                'id'                 => 9,
                'sku'                => '5GF68',
                'name_1'             => '300-Watt Floodlight',
                'shortDescription_1' => 'This 300-watt flood light provides bright and focused illumination.',
                'all_text_1'         => '5GF68 300 watt floodlight flood light provides bright',
                'minimum_price'      => '35.99',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '35.99'
                ],
                'image'              => null

            ],
            [
                'id'                 => 10,
                'sku'                => '8DO33',
                'name_1'             => 'Receipt Printer',
                'shortDescription_1' => 'This receipt printer uses a ribbon to transfer ink to paper',
                'all_text_1'         => '8DO33 receipt printer uses ribben transfer ink paper',
                'minimum_price'      => '240.00',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '240.00'
                ],
                'image'              => null
            ],
            [
                'id'                 => 10,
                'sku'                => 'SDSDUNC-064G-AN6IN',
                'name_1'             => 'Sandisk Ultra SDXC 64GB 80MB/S C10 Flash Memory Card',
                'shortDescription_1' => 'An ultra fast Sandisk Ultra SDXC memory card for various devices...',
                'all_text_1'         => 'SDSDUNC 064G AN6IN sandisk ultra sdxc 64gb flash card',
                'product_units'      => [
                    'item' => 'item'
                ],
                'prices'             => [
                    'item_1' => '250.00'
                ],
                'image'              => 'https://images-na.ssl-images-amazon.com/images/I/51pvrWJX2sL.jpg'
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
    private function readDatabase($fullData, $selectedColumns)
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
     * @param $data
     * @param $criteria
     * @return array
     */
    private function applyFilters($data, Criteria $criteria)
    {
        $result = [];

        if (!$criteria->getWhereExpression()) {
            return $data;
        }

        $expression = $criteria->getWhereExpression();

        foreach ($data as $row) {
            $visitor = new ExampleExpressionVisitor($row);
            if ($visitor->dispatch($expression)) {
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * @param Query $query
     * @param array $data
     * @return array
     */
    private function getOrderedData(Query $query, array $data)
    {
        foreach ($query->getCriteria()->getOrderings() as $field => $sort) {
            list($type, $key) = Criteria::explodeFieldTypeName($field);

            usort(
                $data,
                function ($a, $b) use ($key, $sort) {
                    if ($sort === SearchSorterExtension::DIRECTION_DESC) {
                        $result = strcmp($b[$key], $a[$key]);
                    } else {
                        $result = strcmp($a[$key], $b[$key]);
                    }
                    return $result;
                }
            );
        }

        return $data;
    }

    /**
     * @param Query $query
     * @param       $result
     * @return array
     */
    private function getPaginatedData(Query $query, $result)
    {
        $criteria = $query->getCriteria();

        $offset = $criteria->getFirstResult();

        $limit = $criteria->getMaxResults();

        if (empty($result)) {
            return $result;
        }

        $result = array_slice($result, $offset, $limit);

        return $result;
    }

    /**
     * @param array $fields
     * @return array
     */
    private function resolveLocalizationPlaceholder(array $fields)
    {
        $currentLocalization = $this->currentLocalizationProvider->getCurrentLocalization();
        $localeId            = $currentLocalization->getId();

        $originalFields = $resolvedFieldsBackArray = [];

        foreach ($fields as $key => $field) {
            list ($type, $originalField) = Criteria::explodeFieldTypeName($field);
            $originalFields[] = $originalField;
            $fields[$key]     = str_replace(self::$localizationIdPlaceholder, $localeId, $field);
            list ($type, $translatedField) = Criteria::explodeFieldTypeName($fields[$key]);
            $resolvedFieldsBackArray[] = $translatedField;
        }

        $this->resolvedFieldsBackArray = array_combine(
            $resolvedFieldsBackArray,
            $originalFields
        );

        return $fields;
    }

    /**
     * @param array $result
     * @return array
     */
    private function revertLocalizationPlaceholderInResults(array $result)
    {
        if (empty($this->resolvedFieldsBackArray)) {
            return $result;
        }

        foreach ($result as $key => $row) {
            $keys   = array_keys($row);
            $values = array_values($row);

            array_walk(
                $keys,
                function (&$field) {
                    $field = strtr($field, $this->resolvedFieldsBackArray);
                }
            );

            $result[$key] = array_combine($keys, $values);
        }

        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    private function copyPlaceholderFieldsToNormal(array $result)
    {
        if (empty($result)) {
            return $result;
        }

        foreach ($result as $key => $row) {
            $normalFields = [];

            array_walk(
                $row,
                function ($value, $field) use (&$normalFields) {
                    if (false !== strpos($field, self::$localizationIdPlaceholder)) {
                        $normalKey                = str_replace('_' . self::$localizationIdPlaceholder, '', $field);
                        $normalFields[$normalKey] = $value;
                    }
                }
            );

            $result[$key] = array_merge(
                $row,
                $normalFields
            );
        }

        return $result;
    }
}
