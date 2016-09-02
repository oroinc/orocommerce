<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractGridControllerTest extends WebTestCase
{
    /**
     * @return string
     */
    abstract protected function getGridName();

    /**
     * @param array $filters
     * @param array $sorters
     * @return array
     */
    protected function getDatagridData(array $filters = [], array $sorters = [])
    {
        $result = [];
        foreach ($filters as $filter => $value) {
            $result[$this->getGridName() . '[_filter]' . $filter] = $value;
        }
        foreach ($sorters as $sorter => $value) {
            $result[$this->getGridName() . '[_sort_by]' . $sorter] = $value;
        }
        $response = $this->client->requestGrid(['gridName' => $this->getGridName()], $result);

        return json_decode($response->getContent(), true)['data'];
    }
}
