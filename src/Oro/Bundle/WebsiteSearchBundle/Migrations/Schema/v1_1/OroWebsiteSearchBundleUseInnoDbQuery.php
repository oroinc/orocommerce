<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_1;

use Oro\Bundle\SearchBundle\Migrations\Schema\v1_3\OroSearchBundleUseInnoDbQuery;

class OroWebsiteSearchBundleUseInnoDbQuery extends OroSearchBundleUseInnoDbQuery
{
    /**
     * {@inheritDoc}
     */
    protected function getTableName(): string
    {
        return 'oro_website_search_text';
    }
}
