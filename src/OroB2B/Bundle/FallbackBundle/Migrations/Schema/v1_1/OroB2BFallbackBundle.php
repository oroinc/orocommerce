<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BFallbackBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery('RENAME TABLE `orob2b_fallback_locale_value` TO `oro_fallback_locale_value`');
    }
}
