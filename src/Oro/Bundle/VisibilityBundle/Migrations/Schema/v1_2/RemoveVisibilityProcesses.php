<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WorkflowBundle\Migration\RemoveProcessesQuery;

class RemoveVisibilityProcesses implements Migration
{
    /**
     * List of process definition to remove
     * @var array
     */
    const NAMES = [
        'account_group_product_visibility_change',
        'account_product_visibility_change',
        'change_account_category_visibility',
        'change_account_group_category_visibility',
        'change_category_visibility',
        'product_visibility_category_create',
        'product_visibility_change',
        'product_visibility_product_create',
        'product_visibility_website_added',
    ];

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new RemoveProcessesQuery(self::NAMES));
    }
}
