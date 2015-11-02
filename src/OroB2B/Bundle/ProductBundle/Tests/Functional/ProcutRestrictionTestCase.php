<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class ProcutRestrictionTestCase extends WebTestCase
{

    /**
     * @param array $availableInventoryStatuses
     */
    protected function prepareConfig(array $availableInventoryStatuses)
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_b2b_order.product_visibility.value', $availableInventoryStatuses);

        $configManager->flush();
    }

    /**
     * @dataProvider restrictDataProvider
     * @param array $dataParameters
     * @param array $availableInventoryStatuses
     * @param array $expectedProducts
     * @return mixed
     */
    abstract public function testRestriction(
        array $dataParameters,
        array $availableInventoryStatuses,
        array $expectedProducts
    );


}
