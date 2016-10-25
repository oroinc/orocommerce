<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Driver;

use Oro\Bundle\CustomerBundle\Driver\OrmAccountPartialUpdateDriver;

/**
 * @dbIsolationPerTest
 */
class OrmAccountPartialUpdateDriverTest extends AbstractAccountPartialUpdateDriverTest
{
    /**
     * @return OrmAccountPartialUpdateDriver
     */
    protected function getDriver()
    {
        return $this->getContainer()->get('oro_customer.driver.orm_account_partial_update_driver');
    }
}
