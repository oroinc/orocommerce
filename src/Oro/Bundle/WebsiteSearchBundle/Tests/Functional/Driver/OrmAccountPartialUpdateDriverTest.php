<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Driver;

use Oro\Bundle\WebsiteSearchBundle\Driver\OrmAccountPartialUpdateDriver;

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
        return $this->getContainer()->get('oro_website_search.driver.orm_account_partial_update_driver');
    }
}
