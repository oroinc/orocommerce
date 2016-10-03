<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Driver;

use Oro\Bundle\AccountBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\WebsiteSearchBundle\Driver\OrmAccountPartialUpdateDriver;

/**
 * @dbIsolationPerTest
 */
class OrmAccountPartialUpdateDriverTest extends AbstractAccountPartialUpdateDriverTest
{
    /**
     * {@inheritdoc}
     */
    protected function createDriver(ProductVisibilityProvider $productVisibilityProvider)
    {
        return new OrmAccountPartialUpdateDriver(
            $this->getContainer()->get('oro_website_search.placeholder.visitor_replace'),
            $this->getContainer()->get('oro_website_search.provider.search_mapping'),
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor'),
            $productVisibilityProvider
        );
    }
}
