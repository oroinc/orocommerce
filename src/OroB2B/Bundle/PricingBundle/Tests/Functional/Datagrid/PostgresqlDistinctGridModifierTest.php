<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Datagrid;

use Oro\Bundle\DataGridBundle\Tests\Functional\Extension\PostgresqlGridModifierTest;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolation
 */
class PostgresqlDistinctGridModifierTest extends PostgresqlGridModifierTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists'
        ]);

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->gridName = 'price-list-websites-grid';
        $this->gridParameters = ['price_list_id' => $priceList->getId()];
        $this->identifier = 'priceList.id';
    }
}
