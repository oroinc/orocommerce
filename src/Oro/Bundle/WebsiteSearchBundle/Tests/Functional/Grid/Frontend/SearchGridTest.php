<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Grid\Frontend;

use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\FrontendTestFrameworkBundle\Datagrid\DatagridTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SearchGridTest extends WebTestCase
{
    use DatagridTestTrait;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testSorters()
    {
        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[sku]' => AbstractSorterExtension::DIRECTION_ASC]
        );
        $this->checkSorting($products, 'sku', AbstractSorterExtension::DIRECTION_ASC);
        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[sku]' => AbstractSorterExtension::DIRECTION_DESC,]
        );
        $this->checkSorting($products, 'sku', AbstractSorterExtension::DIRECTION_DESC);
    }

    /**
     * @param array  $data
     * @param string $column
     * @param string $orderDirection
     * @param bool   $stringSorting
     */
    protected function checkSorting(array $data, $column, $orderDirection, $stringSorting = false)
    {
        foreach ($data as $row) {
            $actualValue = $row[$column];

            if (isset($lastValue)) {
                if ($orderDirection === AbstractSorterExtension::DIRECTION_DESC) {
                    $this->assertGreaterThanOrEqual($actualValue, $lastValue);
                } elseif ($orderDirection === AbstractSorterExtension::DIRECTION_ASC) {
                    $this->assertLessThanOrEqual($actualValue, $lastValue);
                }
            }
            $lastValue = $actualValue;
        }
    }
}
