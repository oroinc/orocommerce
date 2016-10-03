<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Driver;

use Oro\Bundle\AccountBundle\Visibility\Provider\AccountProductVisibilityProvider;
use Oro\Bundle\WebsiteSearchBundle\Driver\OrmAccountPartialUpdateDriver;

/**
 * @dbIsolationPerTest
 */
class OrmAccountPartialUpdateDriverTest extends AbstractAccountPartialUpdateDriverTest
{
    /**
     * {@inheritdoc}
     */
    protected function createDriver(AccountProductVisibilityProvider $accountProductVisibilityProvider)
    {
        return new OrmAccountPartialUpdateDriver(
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor'),
            $accountProductVisibilityProvider,
            $this->getContainer()->get('oro_website_search.provider.search_mapping')
        );
    }
}
