<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WorkflowBundle\Migrations\Schema\RemoveWorkflowFieldsTrait;

class RemoveWorkflowFieldsMigration implements Migration
{
    use RemoveWorkflowFieldsTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->removeWorkflowFields($schema->getTable('oro_checkout'));
        $this->removeConfigsForWorkflowFields('Oro\Bundle\CheckoutBundle\Entity\Checkout', $queries);
    }
}
