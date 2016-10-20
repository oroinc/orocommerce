<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Driver;

use Oro\Bundle\AccountBundle\Driver\OrmAccountPartialUpdateDriver;

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
        return $this->getContainer()->get('oro_account.driver.orm_account_partial_update_driver');
    }
}
