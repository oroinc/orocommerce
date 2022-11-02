<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Migration\UpdateNotificationRuleWorkflowTransitionQuery;

/**
 * Change email notification in Backoffice Quote Flow with Approvals on create_new_quote_transition
 */
class OroSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateNotificationRuleWorkflowTransitionQuery(
            Quote::class,
            'b2b_quote_backoffice_approvals',
            'quote_created',
            'create_new_quote_transition',
            '__start__'
        ));
    }
}
