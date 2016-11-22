<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Datagrid;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;

trait DatagridTestTrait
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @param string $gridName
     * @param array  $filters
     * @param array  $sorters
     * @return array
     */
    protected function getDatagridData($gridName, array $filters = [], array $sorters = [])
    {
        $result = [];
        foreach ($filters as $filter => $value) {
            $result[$gridName . '[_filter]' . $filter] = $value;
        }
        foreach ($sorters as $sorter => $value) {
            $result[$gridName . '[_sort_by]' . $sorter] = $value;
        }
        $response = $this->client->requestFrontendGrid(['gridName' => $gridName], $result);

        return json_decode($response->getContent(), true)['data'];
    }
}
