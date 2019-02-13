<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Migration\UpdateNotificationRuleWorkflowTransitionQuery;

/**
 * Updates transition name for email notification on Quote's auto expiration.
 */
class UpdateTransitionForQuoteAutoExpireNotification implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateNotificationRuleWorkflowTransitionQuery(
            Quote::class,
            'b2b_quote_backoffice_approvals',
            'quote_expired_automatic',
            'expire_transition',
            'auto_expire_transition'
        ));
    }
}
